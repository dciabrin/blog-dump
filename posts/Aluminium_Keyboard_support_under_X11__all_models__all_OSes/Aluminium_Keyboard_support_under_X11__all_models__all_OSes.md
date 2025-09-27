title: Aluminium Keyboard support under X11: all models, all OSes
tags: apple-kbd,apple
date: 2009-12-22T22:03:00.008+01:00
category: Code

I finally found the time to update my previous support for Aluminium
Keyboard under Xorg, and take it to the Next Level (tm). The overall
support is now much more polished. For you this means several things:

-   I've implemented the XKB geometries of **all variants of the long
    Aluminium Keyboard**, be it
    [ANSI](http://damien.ciabrini.free.fr/pub/applekbd-xkb/apple-alukbd-ansi.svg)
    (American),
    [ISO](http://damien.ciabrini.free.fr/pub/applekbd-xkb/apple-alukbd-iso.svg)
    (Internationnal) or
    [JIS](http://damien.ciabrini.free.fr/pub/applekbd-xkb/apple-alukbd-jis.svg)
    (Japanese)! And believe me, it's darned complicated to support JIS
    keyboard's dual layout without having access to the real hardware :D

-   I've added support for base XKB rules, which means that the keyboard
    will now be properly configured **on other OSes than Linux**. I
    personally used [OpenSolaris](http://www.opensolaris.org/) during my
    tests, but it should work equally well on
    [FreeBSD](http://www.freebsd.org/), [OpenBSD](http://openbsd.org/),
    and all their cousins!

-   The keyboard support is now **aware of the system-wide keyboard
    settings** as found in Debian or Fedora for example. If you
    configured your system to default to Dvorak layout, the support will
    use it automatically!

<!-- PELICAN_END_SUMMARY -->

Installing the new support
--------------------------

I'm short on details, but you can find a complete explanation for all
these steps in a [previous
post](http://damienciabrini.blogspot.com/2009/05/make-your-apple-aluminium-keyboard.html).

### XKB Patch

First of all, download [this XKB
patch](http://damien.ciabrini.free.fr/pub/applekbd-xkb/applekbd-xkb-support.patch.gz)
and try to apply it on your XKB install in dry-run. For the sake of the
example, I assume you downloaded the patch in your `$HOME` directory.

    ::console
    sudo bash
    cd /usr/share/X11/xkb
    gunzip -cd $HOME/applekbd-xkb-support.patch.gz | patch -p1 --dry-run

If the patch applies successfuly, you can proceed and apply it for real:

    ::console
    gunzip -cd $HOME/applekbd-xkb-support.patch.gz | patch -p1

### HAL support

Well, even if HAL is meant to be replaced sooner or later by
[DeviceKit](http://fedoraproject.org/wiki/Features/DeviceKit), that's
the way to go for the time being. So I reworked the previous HAL support
and split it in one fdi file to track your keyboard on your hardware,
plus a script to configure XKB for Aluminium Keyboard and to remap the
Fn key to Insert. To install it, first remove the fdi file from the
previous support if necessary. Then, copy this [new fdi
file](http://damien.ciabrini.free.fr/pub/applekbd-xkb/10-applekbd-xkb-settings.fdi)
(which you have previously downloaded in your `$HOME` directory) in the
relevant HAL directory. On my Ubuntu Karmic, this gives (**update:**
added missing chmod, thanks Patrick):

    ::console
    sudo bash
    rm -f /usr/share/hal/fdi/policy/30user/10-apple-aluminium-kbd.fdi
    mkdir -p /usr/share/hal/fdi/policy/30user
    cp $HOME/10-applekbd-xkb-settings.fdi /usr/share/hal/fdi/policy/30user

Download (I assume in your `$HOME` directory) the [new script for XKB
setting](http://damien.ciabrini.free.fr/pub/applekbd-xkb/applekbd-xkb-settings.sh),
plus its associated [configuration
file](http://damien.ciabrini.free.fr/pub/applekbd-xkb/applekbd-xkb-settings).
Then copy them respectively in HAL's installation directory and in the
system-wide configuration directory. On my Ubuntu, ths gives:

    ::console
    sudo bash
    cp $HOME/applekbd-xkb-settings.sh /usr/lib/hal
    chmod +x /usr/lib/hal/applekbd-xkb-settings.sh
    cp $HOME/applekbd-xkb-settings /etc/default

You can tweak the configuration file
`/etc/default/applekbd-xkb-settings` to enable the settings you want for
your keyboard. By default, the Fn key is remapped to Insert. The XKB
options can be configured there for system-wide setting, as explained in
the configuration file itself. But it's more user-friendly to configure
XKB in your preferred Desktop Environment (GNOME, KDE, XFCE...
whatever).

Enjoy!
------

I'm particularly happy with my revised XKB geometries. A tedious work,
but this time keys dimension and layout perfectly match the original
hardware, whatever the model. I'm even more happy now that I discovered
how Apple decided to implement their EISU and KANA keys on the JIS
keyboard! If you are curious, have a look at file
`xkb/keycodes/machintosh` in the patch, or read [this post on Parallels'
forum](http://forum.parallels.com/showthread.php?t=90313).

The XKB patches are now ready for submission to the Xorg people
(xkeyboard-config). The rest of the files are also clean enough to start
providing .deb packages for this support! Maybe a good opportunity of
learning [PPA](https://help.launchpad.net/Packaging/PPA) in
[LaunchPad](https://launchpad.net/) :P
