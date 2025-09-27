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
