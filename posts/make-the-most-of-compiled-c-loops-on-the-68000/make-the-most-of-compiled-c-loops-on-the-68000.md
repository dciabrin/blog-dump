<!--
.. title: Make the most of compiled C loops on the 68000
.. slug: make-the-most-of-compiled-c-loops-on-the-68000
.. date: 2025-09-15 19:07:49 UTC+02:00
.. tags: 68000, neogeo, ngdevkit
.. category: Code
.. link: 
.. description: 
.. type: text
-->


The other day I was working on my pet project [ngdevkit][devkit], an open source C development kit for the [Neo Geo][ng] hardware. I needed to write a simple `clear_screen` function, and I chose to do it in C for simplicity, in the hope that this would get efficiently compiled into 68000 assembly. That apparently innocuous task led me to some interesting findings regarding gcc, binutils, and how you can hint the compiler to generate efficient 68000 code for small loops to reclaim some of your precious clock cycles.

<!-- TEASER_END -->

## A quick glimpse at the Neo Geo video hardware

Back in the days, the Neo Geo MVS and AES hardware have gained a stellar reputation for their outstanding graphics capabilities for the time: complex parallax backgrounds, gigantic sprites on screen, thousands of colors... But conceptually, this hardware is surprisingly simple.

The GPU can display sprites, which are essentially moving objects made out of 16×16 graphics tiles. On top of that it can render a fixed layer of 8×8 graphics tiles, which are non-moving objects used for in-game score, health bars and whatnot. The actual graphics content of a tile is stored in ROM and cannot be updated. At run-time, a dedicated Video RAM holds information about the sprites and tiles to render on screen, along with their attributes, such as color palette, position or scale factor. 


## A simple clear screen function

For now, let's just consider the fixed tile layer. This layer overlays the entire visible screen space (320×224 pixels) and is divided into 40×32 tiles of 8×8 pixel. The Video RAM uses one 16 bits word to describe each tile in the layer: 12 bits for a 8×8 tile index in the ROM, and 4 bits for a palette to be used by the GPU to render the tile on screen.

Data for fix tiles are contiguous in Video RAM, so clearing the entire fixed tile layer is just a matter of looping over the Video RAM and setting the proper tile data to obtain a blank screen. So how would that look in C?

Well, unlike regular RAM, The Video RAM is dedicated to the GPU and it cannot be accessed directly by the 68000, as it is not mapped in the 68000 memory address space. Data can only be read from (resp. written to) Video RAM indirectly by accessing three memory mapped GPU registers, to send or receive graphics data over the 68000's data bus:

  - register `REG_VRAMADDR` (mapped at address `0x3c0000`) configures the GPU to read or write a specific address in the Video RAM.
      
  - register `REG_VRAMRW`   (mapped at address `0x3c0002`) allows to 68000 to read or write a 16 bits word from the configured Video RAM address.

  - register `REG_VRAMMOD`  (mapped at address `0x3c0004`) configures an offset to apply to VRAMADDR after each read or write access to the Video RAM. This speeds up iteration over Video RAM for the 68000.


## First implementation of clear screen in C

As explained earlier, clearing the screen consists in writing 40×32 = 1280 consecutive words to the Video RAM via register `REG_VRAMRW`. For the sake of this example, we want to fill the entire fixed layer with tile `0xafe` and palette `0xc`.


```c
#include <ngdevkit/types.h>

#define REG_VRAMADDR ((volatile u16*)0x3c0000)
#define REG_VRAMRW   ((volatile u16*)0x3c0002)
#define REG_VRAMMOD  ((volatile u16*)0x3c0004)

void clear_screen() {
    *REG_VRAMADDR = 0x7000;
    *REG_VRAMMOD = 1;
    for (u16 repeat = 0; repeat < 40*32; repeat++) {
        *REG_VRAMRW = ((0xc<<12) | 0xafe);
    }
}
```

In this example, we first configure the GPU registers to access the base address of the fixed tile layer, which is `0x7000` in Video RAM. We also configure the GPU to increment the Video RAM address register every time we write the tile data into Video RAM. GPU registers are defined as volatile pointers to unsigned words (16 bits), because gcc doesn't know about the auto-increment semantics, so from a pure C perspective, the loop above looks redundant for the compiler and could be optimized out.

Great, with all our excitement and innocence we're now ready to compile our C function!

```console
$ m68k-neogeo-elf-gcc `pkg-config --cflags ngdevkit` -c clear1.c
$ m68k-neogeo-elf-objdump -d clear1.o --visualize-jumps
[...]
00000000 <clear_screen>:
   0:	       4e56 fffc      	linkw %fp,#-4
   4:	       207c 003c 0000 	moveal #3932160,%a0
   a:	       30bc 7000      	movew #28672,%a0@
   e:	       207c 003c 0004 	moveal #3932164,%a0
  14:	       30bc 0001      	movew #1,%a0@
  18:	       426e fffe      	clrw %fp@(-2)
  1c:	   ,-- 6016           	bras 34 <clear_screen+0x34>
  1e:	,--|-> 207c 003c 0002 	moveal #3932162,%a0
  24:	|  |   30bc cafe      	movew #-13570,%a0@
  28:	|  |   302e fffe      	movew %fp@(-2),%d0
  2c:	|  |   3200           	movew %d0,%d1
  2e:	|  |   5241           	addqw #1,%d1
  30:	|  |   3d41 fffe      	movew %d1,%fp@(-2)
  34:	|  '-> 0c6e 04ff fffe 	cmpiw #1279,%fp@(-2)
  3a:	'----- 63e2           	blss 1e <clear_screen+0x1e>
  3c:	       4e71           	nop
  3e:	       4e71           	nop
  40:	       4e5e           	unlk %fp
  42:	       4e75           	rts
```

And this is where reality kicks in and reminds us to enable optimizations before we can experience a sense of awe and wonder. This generated assembly code maintains a frame pointer, which is costly and likely useless for us. Moreover the default `-O0` optimization level stores all local variables on the stack and doesn't even try to eliminate useless `nop` opcodes.

Fair enough, let's recompile our function with sane optimization flags `-fomit-frame-pointer -O2`:

```console
$ m68k-neogeo-elf-gcc `pkg-config --cflags ngdevkit` -fomit-frame-pointer -O2 -c clear1.c
$ m68k-neogeo-elf-objdump -d clear1.o --visualize-jumps
[...]
00000000 <clear_screen>:
   0:	    33fc 7000 003c 	movew #28672,3c0000 <clear_screen+0x3c0000>
   6:	    0000 
   8:	    33fc 0001 003c 	movew #1,3c0004 <clear_screen+0x3c0004>
   e:	    0004 
  10:	    303c 0500      	movew #1280,%d0
  14:	    323c cafe      	movew #-13570,%d1
  18:	,-> 33c1 003c 0002 	movew %d1,3c0002 <clear_screen+0x3c0002>
  1e:	|   5340           	subqw #1,%d0
  20:	'-- 66f6           	bnes 18 <clear_screen+0x18>
  22:	    4e75           	rts
```

Now that's a good looking function. No more use of the stack, because scratch registers `%d0` and `%d1` can hold the values of all local variables. Moreover, the smaller code helps us to notice other optimizations.

Note how gcc is smart enough to detect that the expression `((0xc<<12) | 0xafe)` is constant, so it can skip shifts and bitwise assembly operations and just emit the resulting immediate value at line `14`. The same goes for the loop condition, gcc emits constant 1280 at line `10` in place of the multiplication 40x32. A classic compiler optimization called _constant folding_, but nice nonetheless.

Also note that with `-O2`, we get some levels of loop optimization, such as `-ftree-loop-optimize`. While in our C code we incremented variable `repeat` in the `for` loop until it reaches 1280, gcc reverses the logic: It generates a `do..while` loop that starts from 1280 (line `10`) and decreases the variable until it reaches 0 (line `20`). Why? Because it saves a comparison at each iteration, which in turns saves 6 bytes in memory, and a lot of cycles at run-time.

We're starting to rip the benefits of a C compiler: not thinking about register allocation, and getting optimized code for free. Now that we're pumped, it's time to check whether we can shave more cycles out of that function.


## Down the rabbit-hole of loop optimization

Back to our generated assembly code. On line `18` we see that each iteration of the loop consists in storing half of register `%d1` at a constant memory location. The 68000 instruction set encodes action `movew %d1` efficiently into two bytes, however the constant address `0x3c0002` costs 4 additional bytes in the code, and some cycles to read and decode the address at each iteration. "This is suboptimal" you wonder. "Surely we could use a temporary address register to speed things up. Like maybe `movew %d1, %a0`, and initialize %a0 out of the loop". And you'd be right. So equipped with our good will and still unfazed by that compilation misfortune, let's try to hint gcc to do better.

```c
void clear_screen() {
    *REG_VRAMADDR = 0x7000;
    *REG_VRAMMOD = 1;
    register volatile u16 *vram asm ("a0") = REG_VRAMRW;
    for (u16 repeat = 0; repeat < 40*32; repeat++) {
        *vram = ((0xc<<12) | 0xafe);
    }
}
```

instead of directly writing to a memory location, we can create a local variable set to that memory location, use a gcc extension to tie this variable to a temporary address register, and call it a day.

```console
$ m68k-neogeo-elf-gcc `pkg-config --cflags ngdevkit` -fomit-frame-pointer -O2 -c clear2.c
$ m68k-neogeo-elf-objdump -d clear2.o --visualize-jumps
[...]
00000000 <clear_screen>:
   0:	    33fc 7000 003c 	movew #28672,3c0000 <clear_screen+0x3c0000>
   6:	    0000 
   8:	    33fc 0001 003c 	movew #1,3c0004 <clear_screen+0x3c0004>
   e:	    0004 
  10:	    303c 0500      	movew #1280,%d0
  14:	    323c cafe      	movew #-13570,%d1
  18:	,-> 33c1 003c 0002 	movew %d1,3c0002 <clear_screen+0x3c0002>
  1e:	|   5340           	subqw #1,%d0
  20:	'-- 66f6           	bnes 18 <clear_screen+0x18>
  22:	    4e75           	rts
```

Alas, this has absolutely no effect on the generated code. And that's because you don't outsmart gcc so easily. In fact, in this case, the `-O2` optimization level enables `-fgcse`, or [global common subexpression elimination][gcse]: the value of variable `vram` is determined to be constant at compile-time, so gcc eliminates the variable entirely, and propagates its value back into the `movew` statement we wanted to improve.

Let's assume we won't overreact and turn off optimizations linked to constants expressions. So our next best option is to tweak our function to so that this particular local variable no longer looks constant to gcc. And it turns out we can do so, if we treat `REG_VRAMRW` and its siblings like ELF symbols rather than constants:

```c
extern volatile u16 *REG_VRAMADDR;
extern volatile u16 *REG_VRAMRW;
extern volatile u16 *REG_VRAMMOD;

__asm__ (
".global REG_VRAMADDR\n"
".global REG_VRAMRW\n"
".global REG_VRAMMOD\n"
".set REG_VRAMADDR, 0x3c0000\n"
".set REG_VRAMRW,   0x3c0002\n"
".set REG_VRAMMOD,  0x3c0004\n"
);
```

Here we no longer rely on the C preprocessor to substitute names with constants. We declare regular typed symbols so that gcc can manipulate them. We also leverage the `__asm__` directive to define those symbols in the ELF object and associate them with absolute values. Note that this doesn't create additional variables nor does it consume any space in `.data` or `.bss` segments.

```console
$ m68k-neogeo-elf-objdump -t clear3.o
[...]
SYMBOL TABLE:
00000000 l    df *ABS*	00000000 clear3.c
00000000 l    d  .text	00000000 .text
00000000 l    d  .data	00000000 .data
00000000 l    d  .bss	00000000 .bss
00000000 l    d  .comment	00000000 .comment
003c0000 g       *ABS*	00000000 REG_VRAMADDR
003c0002 g       *ABS*	00000000 REG_VRAMRW
003c0004 g       *ABS*	00000000 REG_VRAMMOD
00000000 g     F .text	0000002a clear_screen
```

Since the value of those symbols are only known by `binutils` tools such `objdump` and `ld`, the compiler is forced to consider them as non-constant, so we no longer need to hint it to use a register. We can keep our `clear_screen` function simple, at the cost of a different symbol definition, which can be set aside.

```c
#include <ngdevkit/types.h>
#include "registers.h"

void clear_screen() {
    *REG_VRAMADDR = 0x7000;
    *REG_VRAMMOD = 1;
    for (u16 repeat = 0; repeat < 40*32; repeat++) {
        *REG_VRAMRW = ((0xc<<12) | 0xafe);
    }
}
```

And with that, we have succesfully hinted gcc to generate what we wanted:

```console
$ m68k-neogeo-elf-gcc `pkg-config --cflags ngdevkit` -fomit-frame-pointer -O2 -c clear3.c
$ m68k-neogeo-elf-objdump -d clear3.o --visualize-jumps
[...]
00000000 <clear_screen>:
   0:	    2079 003c 0000 	moveal 3c0000 <REG_VRAMADDR>,%a0
   6:	    30bc 7000      	movew #28672,%a0@
   a:	    2079 003c 0004 	moveal 3c0004 <REG_VRAMMOD>,%a0
  10:	    30bc 0001      	movew #1,%a0@
  14:	    2079 003c 0002 	moveal 3c0002 <REG_VRAMRW>,%a0
  1a:	    303c 0500      	movew #1280,%d0
  1e:	    323c cafe      	movew #-13570,%d1
  22:	,-> 3081           	movew %d1,%a0@
  24:	|   5340           	subqw #1,%d0
  26:	'-- 66fa           	bnes 22 <clear_screen+0x22>
  28:	    4e75           	rts
```

The memory assignment at line `22` went from 6 bytes to 2 per iteration, which translates into a "good amount of cycles saved" on my patented optimization scale. As an added bonus, references to the GPU memory registers are now demangled correctly by `objdump`, yay!


## Trading headaches for more loop gains

The compiled loop itself is only 3 instructions, which looks reasonably small, but the old-timers already know that we can squeeze a few more cycles out of it. You see, the 68000 instruction set has the `dbra` instruction, which essentially _decrements_ a register and _branches_ to a location, all in a single instruction.

A key detail of the `dbra` operation is that it only stops looping when the decremented register reaches -1, not 0. So in order to convince gcc to generate the right opcode, the source code must contain a loop that matches that semantics. That is something that can be easily achieved with a `do..while` loop:

```c
    s16 repeat = 1279;
    do {
        *REG_VRAMRW = ((0xc<<12) | 0xafe);
    } while (--repeat != -1);
}
```

This looks a bit convoluted, but the high-level intention is still clear enough for a human and for gcc...

```console
  1a:	    303c 0500      	movew #1280,%d0
  1e:	    323c cafe      	movew #-13570,%d1
  22:	,-> 3081           	movew %d1,%a0@
  24:	|   5340           	subqw #1,%d0
  26:	'-- 66fa           	bnes 22 <clear_screen+0x22>
```

... well maybe gcc needs a little extra convincing argument... Even though awe and wonder seem far away by now, line `1a` hints us as to what is actually going on. The compiler determined at compile-time that our loop performs a total of 1280 iterations, so the `-ftree-loop-optimize` optimization substituted our C code with a classic `do..while` loop from 1280 down 0. So what do we do from here?

  1. We could double down and obfuscate the C code to prevent gcc from determining the iteration count at compile-time, thus eliminating the unwanted optimization. One can do so at no extra cost with the right inline `__asm__` directive, but at this stage the premise of using C for simplicity seems to become a bit of a stretch.

  2. We acknowledge that gcc has its own view of optimized code, which sometimes conflicts with ours. And in such cases, we should resort to selectively disable a counterproductive optimization. Luckily for us, this can be achieve in gcc by relying on [function attributes][fa].
  
The `optimize` function attribute is a directive that can be used to override the compilation flags for a single function. This is great because we can keep our general compilation flags untouched, and selectively annotate functions that require a special treatment. In the case of our `clear_screen` function, we want to disable `-ftree-loop-optimize` that comes with the `-O2` optimization level:

```c
#include <ngdevkit/types.h>
#include "registers.h"

__attribute__((optimize("no-tree-loop-optimize")))
void clear_screen() {
    *REG_VRAMADDR = 0x7000;
    *REG_VRAMMOD = 1;
    s16 repeat = 1279;
    do {
        *REG_VRAMRW = ((0xc<<12) | 0xafe);
    } while (--repeat != -1);
}
```

That final C function doesn't look much different from the initial implementation. Moreover, the optimization hint lives outside of the body, so is not too disturbing to read either. This is enough to make gcc generate a fairly optimized loop code now:

```console
$ m68k-neogeo-elf-gcc `pkg-config --cflags ngdevkit` -fomit-frame-pointer -O2 -c clear4.c
$ m68k-neogeo-elf-objdump -d clear4.o --visualize-jumps
[...]
00000000 <clear_screen>:
   0:	    2079 003c 0000 	moveal 3c0000 <REG_VRAMADDR>,%a0
   6:	    30bc 7000      	movew #28672,%a0@
   a:	    2079 003c 0004 	moveal 3c0004 <REG_VRAMMOD>,%a0
  10:	    30bc 0001      	movew #1,%a0@
  14:	    2079 003c 0002 	moveal 3c0002 <REG_VRAMRW>,%a0
  1a:	    303c 04ff      	movew #1279,%d0
  1e:	    323c cafe      	movew #-13570,%d1
  22:	,-> 3081           	movew %d1,%a0@
  24:	'-- 51c8 fffc      	dbf %d0,22 <clear_screen+0x22>
  28:	    4e75           	rts
```

Just like that, we squeezed out another instruction from the loop. The compiler generated a `dbf` instruction, which is essentially the same as a `dbra`. The entire loop is still 6 bytes long, like before, but it only takes 18 clock cycles to execute a full iteration, compared to 22 cycles for the 3 instructions variant. Hey, there are no small wins for loops!

## Are we done winning yet?

We now have a very respectable assembly code for the `clear_screen` function: the code only uses registers to write to the Video RAM, which is as fast as it can get. Besides, only scratch registers are used, so no stack usage is required. At last, the looping condition is as efficient as possible.

If we were to split hairs, we could argue that there are still a handful of bytes to gain when initializing the Video RAM access, between lines `0` and `14`. That is because the generated code uses 32 bit immediates to access GPU registers. And let's face it, this optimization journey was always about splitting hairs in the first place... so let's observe that the three GPU registers are two bytes apart in the memory address space of the 68000. This means we could very well add a totally unnecessary last optimization here:

```c
#define REG_VRAMADDR (REG_VRAMRW-1)
#define REG_VRAMMOD  (REG_VRAMRW+1)
```

And gcc would in theory notice that the same base addresses are used for all three registers, eliminate constant subexpressions and generate writes that use indexed memory accesses, resulting in smaller and faster code. And for once, our intuition is correct:

```console
00000000 <clear_screen>:
   0:	    2079 003c 0002 	moveal 3c0002 <REG_VRAMRW>,%a0
   6:	    317c 7000 fffe 	movew #28672,%a0@(-2)
   c:	    317c 0001 0002 	movew #1,%a0@(2)
  12:	    303c 04ff      	movew #1279,%d0
  16:	    323c cafe      	movew #-13570,%d1
  1a:	,-> 3081           	movew %d1,%a0@
  1c:	'-- 51c8 fffc      	dbf %d0,1a <clear_screen+0x1a>
  20:	    4e75           	rts
```

Only a single reference to constant address `0x3c0002`, indexed memory accesses to Video RAM registers at line `6` and `c`, and the same scratch address register is reused throughout the code. That's it. We're done, mission accomplished.


## Conclusion

After some initial struggles, we have shown that it is possible to use C instead of assembly for developing on retro hardware, where processing and memory resources are scarce and precious. With proper guidance, gcc is able to generate assembly code that is on par with what could be produced manually.

Was this whole exercise worth it? I'd say yes. I think it demonstrated that with a modern toolchain, you no longer need to write you entire project in assembly, as you may have done back in the days. Writing in assembly is great when strict timing or performance is required. But when it's not, a higher-level language like C really shines, as it's generally faster to iterate, and you can delegate tedious tasks such as register allocation or code optimization to the C compiler.

What lessons have we learned with this small experiment? I think it is clear by now that relying blindly on the C compiler for performance will only bring disappointment. You still need to have a good understanding of your execution platform and where you want to spend time optimizing code. Moreover, when targeting a single platform, you should look at non-standard [extensions][ext] and [optimizations flags][extasm] offered by your compiler, as they may be worth using in your projects.

As often, what started as a deceptively simple task ended up being a massive endeavor... It was a nice experience nonetheless as I learned a few things along the way, and I hope you did too.


[ng]: https://en.wikipedia.org/wiki/Neo_Geo
[devkit]: https://github.com/dciabrin/ngdevkit
[gcse]: https://gcc.gnu.org/news/gcse.html
[fa]: https://gcc.gnu.org/onlinedocs/gcc/Function-Attributes.html
[ext]: https://gcc.gnu.org/onlinedocs/gcc/C-Extensions.html
[extasm]: https://gcc.gnu.org/onlinedocs/gcc/Using-Assembly-Language-with-C.html
