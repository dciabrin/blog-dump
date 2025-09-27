#include <ngdevkit/types.h>

#define REG_VRAMADDR ((volatile u16*)0x3c0000)
#define REG_VRAMRW   ((volatile u16*)0x3c0002)
#define REG_VRAMMOD  ((volatile u16*)0x3c0004)

// Doesn't generate a0, because of -fgcse optimization
void clear_screen() {
    *REG_VRAMADDR = 0x7000;
    *REG_VRAMMOD = 1;
    register volatile u16 *vram asm ("a0") = REG_VRAMRW;
    for (u16 repeat = 0; repeat < 40*32; repeat++) {
        *vram = ((0xc<<12) | 0xafe);
    }
}
