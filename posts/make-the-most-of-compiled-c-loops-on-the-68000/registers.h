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
