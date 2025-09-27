title: Toolchain.sh is amazing!
tags: iphone,apple
date: 2009-02-23T21:53:00.003+01:00
category: Code

A few weeks ago, I finally upgraded my iPhone to firwmare 2.2.1. It was
actually an utterly painless operation, thanks to the amazing
[toolchain.sh](http://code.google.com/p/iphonedevonlinux/wiki/Installation)
by the guys from
[iphonedevlinux](http://code.google.com/p/iphonedevonlinux/).

<!-- PELICAN_END_SUMMARY -->

I remember in the 1.1.4 days, I had a hard time extracting the MacOS
10.5 SDK on Linux and compiling the openSDK on my Core2 Quad by
following [saurik's great instructions](http://www.saurik.com/id/4).

It turns out that building an open SDK for firmware 2.2.1 is actually
very simple: getting a copy of the phone's sysroot, building GCC,
importing headers from the official iphone SDK, classdumping private
frameworks... all these operations are now almost automatic thanks to
toolchain.sh.

The icing on the cake: those smart people from
[iphonedevlinux](http://code.google.com/p/iphonedevonlinux/) are
friendly and reactive! Another good reason for
[sending](http://code.google.com/p/iphonedevonlinux/issues/detail?id=7)
[them](http://code.google.com/p/iphonedevonlinux/issues/detail?id=6&can=1#c15)
[patches](http://code.google.com/p/iphonedevonlinux/issues/detail?id=10)
and using their script :)
