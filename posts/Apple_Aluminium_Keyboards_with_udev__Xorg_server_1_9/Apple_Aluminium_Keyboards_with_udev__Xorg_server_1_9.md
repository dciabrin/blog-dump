title: Apple Aluminium Keyboards with udev, Xorg server 1.9
tags: apple-kbd,apple
date: 2011-01-11T22:48:00.004+01:00
category: Code

It's been a year now since I published my support for Aluminium
Keyboards. Since then, my XKB patches have been accepted in
[XKeyboardConfig](http://freedesktop.org/wiki/Software/XKeyboardConfig)
1.9, with a few modifications:

-   The multimedia keys can always be accessed by combining Fxx with the
    3rd level chooser (this was option `alul3media`{#tt1099} in my
    original XKB patches)

-   There is now a single XKB option `alupckeys`{#tt1101} to emulate the
    behaviour of a PC keyboard, *i.e.* to enable PrintScreen,
    ScrollLock, SysReq and NumLock (options `alupcfkeys`{#tt1103} and
    `alupcnumlock`{#tt1104} in the original patches)

<!-- PELICAN_END_SUMMARY -->

Meanwhile, Xorg server 1.9 went stable, becoming more and more
pervasive. As far as input hotplugging is concerned, this is a major
revision for it dropped
[HAL](http://www.freedesktop.org/wiki/Software/hal) in favor of
[udev](http://www.kernel.org/pub/linux/utils/kernel/hotplug/udev.html):
input discovery is achieved via udev and XKB settings for devices are
fetched from the udev database.

I have thus ported the support for the Aluminium Keyboards to udev. As
before, a configuration file controls the XKB settings to apply, as well
as the remapping of the "fn" key to "insert", if requested.

Installing the udev-enabled support
-----------------------------------

First, download the [necessary udev
rules](http://damien.ciabrini.free.fr/pub/alu-kbd-udev/95-keymap-apple-kdb.rules)
and install them in whatever directory your distrib uses to store user
rules. On Ubuntu, assuming that you downloaded the rules in your home
directory, this gives:

    ::console
    sudo cp $HOME/95-keymap-apple-kdb.rules /etc/udev/rules.d

Then, download the [configuration
file](http://damien.ciabrini.free.fr/pub/alu-kbd-udev/apple-kbd) and
install it in your distrib's configuration directory. On Ubuntu, this
gives:

    ::console
    sudo cp $HOME/apple-kbd /etc/default

The configuration file contains various key-value pairs that drive the
behaviour of the Aluminium Keyboard. By default, the configuration
enables the XKB option for PC-like mapping (PrintScreen, ScrollLock,
Pause, NumLock) and maps the "fn" key to "insert". Comment out the
relevant lines to disable any of those settings if necessary.

There's a catch: what's your distrib?
-------------------------------------

The udev-enabled support assumes one thing: that you are running
xkeyboard-config 1.9 or above. Not all distribs are equal in this
regard.

A quick search shows that Arch, Gentoo, Fedora or openSUSE all ship a
recent-enough xkeyboard-config. On the other hand, Debian is currently
stuck with xkeyboard-config 1.8-2 (at least for unstable, I haven't
checked experimental).

The Ubuntu case is the most puzzling one. At the time of writing,
Maverick and Natty ship xkeyboard-config 1.8-1ubuntu8, which is based on
1.8 stock plus additional important commits from the git repository.
This includes my patches for the Aluminium Keyboard (!), but
unfortunately only 6 patches out of 7 have been included (!?!).
Consequently, the XKB support is currently broken for Ubuntu. I have
filled [bug 696232](https://bugs.launchpad.net/bugs/696232) in Launchpad
to track this issue and check whether this was intended or not.
Meanwhile, you can grab [this XKB
patch](http://damien.ciabrini.free.fr/pub/alu-kbd-udev/xkb-data-1.8-evdev.patch)
and try to apply it:

    ::console
    sudo bash
    cd /usr/share/X11/xkb/rules
    patch -p0 --dry-run < $HOME/xkb-data-1.8-evdev.patch

If the patch applies successfully, you can proceed and apply it for
real:

    ::console
    patch -p0 < $HOME/xkb-data-1.8-evdev.patch

Steps to come
-------------

With this updated support, Aluminium Keyboards work again under recent
versions of the Xorg server, but there are still various improvements to
think about. The next step is to package the support to make it more
user-friendly. A package is definitely a good place to deal with other
keyboard options, such as kernel settings for activating multimedia keys
*vs.* function keys.

On the XKB side, it would be necessary to perform a second pass to
ensure that there are no missing or misplaced symbols on the 16 existing
keymaps: ANSI, JIS and the 14 ISO layouts.

Speaking of misplaced symbols: I am currently checking whether it's
possible to come with a fully user-space, udev-based solution to the
["keys swapped"
issue](https://bugs.launchpad.net/ubuntu/+source/linux/+bug/214786) that
plague some owners of the ISO variants. I have some ideas, but this will
be another post!
