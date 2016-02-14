title: Support for Aluminium Keyboards packaged, code-named apple-kbd
tags: apple-kbd,apple
date: 2011-05-19T21:35:00.000+02:00
category: Code

After many episodes, the support for Apple Aluminium Keyboards is
finally becoming user-friendly. All major distribs now ship a recent
version of
[xkeyboard-config](http://www.freedesktop.org/wiki/Software/XKeyboardConfig),
so there is no need to mess with XKB patches anymore...

To complete the user experience, I'm happy to introduce you
[`apple-kbd`{#tt1099}](https://github.com/dciabrin/apple-kbd), the
collection of helpful goodies you need for your Aluminium Keyboard under
Linux. Here's what you'll get with this package:

<!-- PELICAN_END_SUMMARY -->

Automatic keyboard detection under X
:   At boot time or when the keyboard is plugged in,
    `apple-kbd`{#tt1101} auto-updates the system-wide XKB settings so
    that the X server sees the Aluminium Keyboard and enables all its
    keys and its geometry.

Key style preferences
:   `apple-kbd`{#tt1103} lets you remap some of the keyboard's keys:
    you'll get back the Insert key, the antique Print, Scroll-Lock and
    Pause... You can also set the precedence of functions keys over
    multimedia keys.

User-friendly installation
:   Both auto-detect and configuration features are available in a
    single, easy to install package. If you're running Debian or Ubuntu,
    there's even a package for you which comes with interactive
    configuration thanks to debconf!

Installing the debian package of apple-kbd
------------------------------------------

I made a [PPA on
Launchpad](https://launchpad.net/~damien-ciabrini/+archive/apple-kbd) to
package `apple-kbd`{#tt1107}, so it's super easy to install it on your
Debian or Ubuntu release. The plus of the Debian version compared to the
plain sources is that you'll get a graphical dialog to configure your
key style preferences. The dialog is also localized, only in French for
the time being, but translators are welcome!

### Automatic installation for Ubuntu Natty Narwhal

To install `apple-kbd`{#tt1109} on Natty, you basically have to follow
the instructions found on Launchpad. Just add the PPA to your list of
available locations:

    sudo add-apt-repository ppa:damien-ciabrini/apple-kbd

And once the PPA repository and its GPG key are imported, you can
install the package as usual:

    sudo apt-get update
    sudo apt-get install apple-kbd

The first time you'll install the package, you'll be asked some
questions regarding the behaviour of the keyboard, *i.e.*, Insert key,
PC keys emulation... At any time, you can reconfigure your keyboard by
typing:

    sudo dpkg-reconfigure apple-kbd

### Manual installation for the others

Oh damned, you don't run Natty (like me...)! No worries, you can still
proceed the Old Way. Just edit `/etc/apt/sources.list`{#tt1117} and add
the following line at the end of the file:

    deb http://ppa.launchpad.net/damien-ciabrini/apple-kbd/ubuntu natty main

And you're good to go! Resynchronize the index of available packages and
install `apple-kbd`{#tt1120}:

    sudo apt-get update
    sudo apt-get install apple-kbd

Don't want a Debian package? Get the sources!
---------------------------------------------

The simplest way of getting [the sources form
GitHub](https://github.com/dciabrin/apple-kbd) is to download the latest
`apple-kbd`{#tt1123} archive:

    wget --no-check-certificate https://github.com/dciabrin/apple-kbd/tarball/apple-kbd-0.1 -Oapple-kbd-0.1.tar.gz
    tar zxvf apple-kbd-0.1.tar.gz

But you can of course fork my git repository to play with it:

    git clone git://github.com/dciabrin/apple-kbd.git

Have a look at `README.rst`{#tt1128} for the details. Basically, you
want to edit the file `apple-kbd`{#tt1129} to configure your keyboard
preferences (Insert key, PC keyboard emulation...). Then, you need the
usual:

    make
    sudo make install

I'm unfortunately a bit lazy, so contrary to the Debian package, you
will need to reboot, or at least to re-plug the keyboard and restart the
X server for your configuration to take effect. In a future version I
will provide a command-line tool to force configuration changes to take
effect on-the-fly.

Is the support finished?
------------------------

I believe `apple-kbd`{#tt1133} is really a milestone in the support of
the Aluminium Keyboards. But of course, everything's perfectible. I
actually see two important things that remain to do. The first one is to
check whether the [longstanding ISO-swapped-keys
bug](https://bugs.launchpad.net/ubuntu/+source/linux/+bug/214786) is
really fixed for all the layouts. The second is to add a XKB geometry
for the Wireless Aluminium Keyboards (the short ones). Plenty of work in
perspective...

So here it is, tell the world about `apple-kbd`{#tt1135}, future will
tell if this package is useful!
