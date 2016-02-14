title: MikMod library ported to iPhone
tags: apple-kbd,apple
date: 2008-06-27T23:18:00.007+02:00
category: Code

I've always been a big fan of old skool computer music, like chiptunes
or demoscene music. Perhaps because they remind me the Good Old Days
(tm) of my [Amiga](http://en.wikipedia.org/wiki/Amiga). Or perhaps for
the same reason I like [demoscene](http://pouet.net/): real-time
computer art! Because it's definitely cool, I've decided to port MikMod
to the iPhone! We'll see later if it might be useful to someone else :)
<span style="font-size:130%;">Compiling libmikmod</span> Here are the
steps to follow to build libmikmod for your iPhone or iPod Touch. First
of all, make sure that you have the necessary header file from Apple,
namely <span style="font-size:100%;"><span
style="font-family:courier new;">AudioQueue.h</span></span>. This file
is part of the AudioQueue framework, which is available in the [MacOS X
10.5 SDK](http://developer.apple.com/tools/download). This means that
the official iPhone SDK is not required. Then, download the latest
[libmikmod-3.2.0-beta2](http://mikmod.raphnet.net/files/libmikmod-3.2.0-beta2.tar.gz)
available on the [MikMod homepage](http://mikmod.raphnet.net/), as well
as [this patch for iPhone
support](http://damien.ciabrini.free.fr/pub/mikmod-iphone/iphone-drv-mikmod-3.2.0-beta2.patch.gz).
For simplicity, let's consider that both files will be downloaded in the
same directory. Once you have them, extract the archive and apply the
patch:

    tar -zxf libmikmod-3.2.0-beta2.tar.gz
    cd libmikmod-3.2.0-beta2
    gunzip -cd ../iphone-drv-mikmod-3.2.0-beta2.patch.gz | patch -p1

Among other things, the patch modifies various Makefiles and the
configure script, so we have to cleanly regenerate all the
autotool-related files:

    aclocal
    automake
    autoconf

Now let's set up the necessary environment variables to configure and
build libmikmod. First, where to find the AudioQueue header and where to
install libmikmod:

    export AQDIR=$HOME/local/audioqueue
    export MMDIR=$HOME/local/mikmod-iphone

Make sure you are using absolute paths for the variables above. Then,
let's set up some compilation flags and name the tools we'll use from
the iPhone toolchain:

    export CFLAGS="-I$AQDIR -DAVAILABLE_MAC_OS_X_VERSION_10_5_AND_LATER="
    export CPPFLAGS="$CFLAGS"
    export LDFLAGS="-framework AudioToolbox"
    export CC=arm-apple-darwin-gcc
    export RANLIB=arm-apple-darwin-ranlib

Okay, now it's time to let configure do its job:

    ./configure --enable-iphone --host=arm-apple-darwin --disable-oss --disable-esd --prefix=$MMDIR

We're almost done! But there's still a little quirk that must be
addressed. The project is configured to build shared libraries, which
for some reason refuse to link with the version of the open-source
iPhone toolchain I use. To overcome this problem, you just need to patch
the generated libtool configuration. Knowing no clever means to do so, I
propose something like:

    sed -r -i 's/^(allow_undefined_flag.*)"/\1 -Wl,-read_only_relocs,suppress"/' libtool

Phew, now we're done! Just type:

    make CFLAGS="$CFLAGS"
    make install

And voila! a fresh libmikmod with support for your beloved iPhone or
iPod Touch! Oh, by the way: the library comes in both static and dynamic
flavor, so it should be pretty usable. Of course, the obligatory example
will follow soon :P <span style="font-weight: bold;">EDIT: </span>the
link to the patch was pointing to a plain file instead of a gzip one,
fixed!
