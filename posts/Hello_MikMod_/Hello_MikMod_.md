<!--
.. title: Hello MikMod!
.. tags: apple-kbd,apple
.. date: 2008-07-05T00:27:00.003+02:00
.. category: Code
-->

As promised earlier, I've packaged an obligatory example of how to use
the iPhone port of [libmikmod](http://mikmod.raphnet.net/) which I've
talked about recently. Go grab [this
tarball](http://damien.ciabrini.free.fr/pub/mikmod-iphone/HelloMikMod.tar.gz)
which implements a very simple "Hello World!":

-   It shows how to play a module within a
    [UIKit](http://www.cocoadev.com/index.pl?UIKit) application. This is
    an adaptation of the skeleton program found in [MikMod
    Documentation](http://http//mikmod.raphnet.net/#docs).

-   It shows how to use the higher level sound API
    [Celestial](http://www.cocoadev.com/index.pl?CelestialFramework) to
    control mikmod output. For instance, how to react to volume change
    events when iPhone buttons are pressed.

<!-- TEASER_END -->

What to do with this archive once you've extracted it?

    ::console
    tar -zxf HelloMikMod.tar.gz
    cd HelloMikMod

Simple! Modify the Makefile to set the location of the <span
style="font-family:courier new;">AudioQueue.h</span> header, as well as
the location where you've installed libmikmod. Once you're done, the
makefile should look like:

    ::makefile
    # directory where AudioQueue.h is located
    AQDIR=$(HOME)/local/audioqueue
    # directory where MikMod is located
    MMDIR=$(HOME)/local/mikmod-iphone
    # comment the following line when the previous settings are OK for you
    #$(error configure AudioQueue and Mikmod location in the Makefile first)

Then, just build and install the app by typing:

    ::console
    make
    scp -r HelloMikMod.app root@iphone:/Applications

Where <span style="font-family:courier new;">iphone</span> stands for
the hostname or the IP of your iPhone. Note that you will need either
curl or wget installed or your machine so that the build process is able
to download the module played in this example app (fortunately, one of
those should always be available on MacOS X or on your favorite Linux
distro). For your convenience, the <span
style="font-family:courier new;">HelloMikMod.app</span> directory is
standalone: it contains the app itself linked statically to libmikmod, a
launcher descriptor for Springboard and the module to play. For my own
pleasure, the mod played is [Stardust
Memories](http://modarchive.org/module.php?59344) by Jester / Sanity, my
all time favorite Amiga mod (see the compo [World of
Commodore](http://www.pouet.net/prod.php?which=2938)). This is only a
simple example of how to use MikMod on iPhone. I've started implementing
a more complex app that mimics the iPod interface for playing mods
(Module DB, Screenshot of associated demos...). Now if only I could use
[Nectarine](http://www.scenemusic.net/) or
[Pouet.net](http://pouet.net/) DB to make some kind of systematic
indexing available!! (sigh....)
