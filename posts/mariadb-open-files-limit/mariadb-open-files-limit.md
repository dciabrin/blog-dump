<!--
.. title: Troubleshooting open_files_limit in MariaDB
.. tags: mariadb,galera
.. date: 2016-03-22T15:01:05+0100
.. category: Code
-->

It may happen in the MariaDB logs that you see failures to set `open_files_limit`:

    ::text
    160318 21:48:04 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160318 21:48:04 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160318 21:48:04 [Warning] Could not increase number of max_open_files to more than 1024 (request: 4907)

Meaning MariaDB was unable to raise the limit of maximum file descriptors at startup, with all the subsequent problems it can cause. Sometimes it is simply due to a bad setting in configuration files, such as:

    ::text
    open_files_limit=-1

<!-- TEASER_END -->


## How MariaDB processes option open_files_limit

When started, MariaDB follows an internal logics to set the limit of file descriptor to use at run-time: 

  * It computes the minimum number of _wanted\_files_, whichever is the biggest from:
    - fd needed by MariaDB and innodb (based on some heuristics)
    - 5 * max_connections as set in config file

  * It sets the new process limit (`setrlimit`) to whichever is the biggest:
    - _wanted\_files_ as computed above
    - or value of option `open_files_limit` (e.g. set in server.cnf)

Now, if the MariaDB configuration files contain a line like:
  
    ::text
    open_files_limit=-1

The signed value will be adjusted automatically by MariaDB to match the expected uint range:

    ::text
    160105  9:10:50 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160105  9:10:50 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295

The side effect is that `setrlimit` will now be called with 4294967295, which fails with `EPERM`[^eperm] even when run as root because the requested value which is above system limits. The per-process limit will thus stick to the default, which is usually 1024 fd. MariaDB will signal the failure by logging the value originally computed for _wanted\_files_:

    ::text
    160105  9:10:50 [Warning] Could not increase number of max_open_files to more than 1024 (request: 9003)

## Config file or command line

One noteworthy detail is that one can ask MariaDB to raise the file descriptors limit at the command line as well, with argument `--open-files-limit=XXX`. In fact, at MariaDB startup, `mysqld_safe` scans both configuration file and command line for option `open_files_limit` and if found, it will pass that value[^config] explicitly at command line when it spawns the `mysqld` server.

The `mysqld` server itself first parses options specified in the configuration files, and after that those coming from the command line. Given the way `mysqld_safe` parses option `open_files_limit`, you can see that the `mysqld` server will parse the option twice if it comes from the configuration file.

## Concrete examples from the logs

Back to the original example from this article:

    ::text
    160318 21:48:04 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160318 21:48:04 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160318 21:48:04 [Warning] Could not increase number of max_open_files to more than 1024 (request: 4907)

You can extract from those logs that option `open_files_limit` was set to -1 somewhere in the config files, and that no command line option `--open-files-limit` was passed to `mysqld_safe` to override it. When parsing the options, `mysqld` logged a bound check warning for the value coming from the configuration file, and another one for the value forwarded by `mysqld_safe` via the command line. Corrected value was too high for `setrlimit`, which consequently failed.

Another pattern that can arise is when MariaDB is used with Galera replication. At startup, `mysqld_safe` needs to run `mysqld` once with special flags to recover the replication position of the galera node. It then start `mysqld` a second time with the proper replication start position. This has the effect of having twice as many warning messages in the logs.

    ::text
    160322 13:07:14 mysqld_safe Starting mysqld daemon with databases from /var/lib/mysql
    160322 13:07:14 mysqld_safe WSREP: Running position recovery with --log_error='/var/lib/mysql/wsrep_recovery.uuL8VZ' --pid-file='/var/lib/mysql/db2-recover.pid'
    160322 13:07:14 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160322 13:07:14 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160322 13:07:14 [Warning] Could not increase number of max_open_files to more than 1024 (request: 2859)
    160322 13:07:16 mysqld_safe WSREP: Recovered position c87b7e3e-ec54-11e5-92b3-16a45d02f190:5
    160322 13:07:16 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160322 13:07:16 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160322 13:07:16 [Note] WSREP: wsrep_start_position var submitted: 'c87b7e3e-ec54-11e5-92b3-16a45d02f190:5'
    160322 13:07:16 [Warning] Could not increase number of max_open_files to more than 1024 (request: 2859)

If MariaDB/Galera is started with a valid `--open-files-limit` argument at the command line, you will only see one bound check warning in the logs per mysqld run:

    ::text
    160322 13:23:22 mysqld_safe Starting mysqld daemon with databases from /var/lib/mysql
    160322 13:23:22 mysqld_safe WSREP: Running position recovery with --log_error='/var/lib/mysql/wsrep_recovery.WAKIoR' --pid-file='/var/lib/mysql/db2-recover.pid'
    160322 13:23:22 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295
    160322 13:23:24 mysqld_safe WSREP: Recovered position c87b7e3e-ec54-11e5-92b3-16a45d02f190:5
    160322 13:23:24 [Warning] option 'open_files_limit': unsigned value 18446744073709551615 adjusted to 4294967295

## Checking whether open_files_limit setting is active

In order to change `open_files_limit`, you should start MariaDB as root and use option `--user` to let `mysqld` switch to the requested user after setting limits. If you don't see complaints in the logs, `open_files_limit` setting should be applied. Under Linux, a quick means of verifying that is to probe the running `mysqld` process:

    ::console
    # cat /proc/$(pidof /usr/libexec/mysqld)/limits | grep -e Limit -e 'open files'
    Limit                     Soft Limit           Hard Limit           Units
    Max open files            10245                10245                files

Likewise, the `mysql` client will return the limit that has been successfully set:

    ::console
    # mysql -e "SHOW VARIABLES LIKE 'open_files_limit';"
    +------------------+-------+
    | Variable_name    | Value |
    +------------------+-------+
    | open_files_limit | 10245 |
    +------------------+-------+

Don't be surprised if you don't see the exact value you specified for `open_files_limit`. Remember that MariaDB will call `setrlimit` with the highest value between _wanted\_files_ and `open_files_limit`.

If `Soft Limit` or the `mysql` client reports something like 1024, that means `mysqld` did not raise the maximum file descriptor limit appropriately, and the logs should contain enough information to find out why.

[^eperm]: From setrlimit man: EPERM  The caller tried to increase the hard RLIMIT_NOFILE limit above the maximum defined by /proc/sys/fs/nr_open (see proc(5))
[^config]: If set in configuration file and at the command line, the latter takes precedence over the former
