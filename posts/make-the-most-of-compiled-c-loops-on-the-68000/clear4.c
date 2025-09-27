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
