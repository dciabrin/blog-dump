#include <ngdevkit/types.h>
#include "registers.h"

void clear_screen() {
    *REG_VRAMADDR = 0x7000;
    *REG_VRAMMOD = 1;
    for (u16 repeat = 0; repeat < 40*32; repeat++) {
        *REG_VRAMRW = ((0xc<<12) | 0xafe);
    }
}
