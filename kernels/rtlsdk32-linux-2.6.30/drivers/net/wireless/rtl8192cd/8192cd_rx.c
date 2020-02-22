/*
 *  RX handle routines
 *
 *  $Id: 8192cd_rx.c,v 1.27.2.31 2010/12/31 08:37:43 davidhsu Exp $
 *
 *  Copyright (c) 2009 Realtek Semiconductor Corp.
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License version 2 as
 *  published by the Free Software Foundation.
 */

#define _8192CD_RX_C_

#ifdef __KERNEL__
#include <linux/module.h>
#include <linux/netdevice.h>
#include <linux/etherdevice.h>
#include <net/ip.h>
#include <linux/ipv6.h>
#include <linux/icmpv6.h>

#elif defined(__ECOS)
#include <cyg/io/eth/rltk/819x/wrapper/sys_support.h>
#include <cyg/io/eth/rltk/819x/wrapper/skbuff.h>
#include <cyg/io/eth/rltk/819x/wrapper/timer.h>
#include <cyg/io/eth/rltk/819x/wrapper/wrapper.h>
#endif

#include "./8192cd_cfg.h"
#include "./8192cd.h"
#include "./8192cd_hw.h"
#include "./8192cd_headers.h"
#include "./8192cd_rx.h"
#include "./8192cd_debug.h"

#ifdef __LINUX_2_6__
#ifdef CONFIG_RTL8672
#include "./romeperf.h"
#else
#include <net/rtl/rtl_types.h>
#endif
#endif

#ifndef __ECOS
#ifdef NOT_RTK_BSP
#include "br_private.h"
#else
#include <../net/bridge/br_private.h>
#endif
#endif

#ifdef CONFIG_RTK_MESH
#include "../mesh_ext/mesh_route.h"
#endif
#if defined(CONFIG_RTL_WAPI_SUPPORT)
#include "wapiCrypto.h"
#endif
#if defined(CONFIG_RTL_FASTBRIDGE)
#include <net/rtl/features/fast_bridge.h>
#endif

#ifdef CONFIG_RTL867X_VLAN_MAPPING
#include "../../re_vlan.h"
#endif

#ifdef BR_SHORTCUT
#ifdef WDS
__DRAM_IN_865X unsigned char cached_wds_mac[MACADDRLEN];
__DRAM_IN_865X struct net_device *cached_wds_dev = NULL;
#endif
#ifdef CONFIG_RTK_MESH
__DRAM_IN_865X unsigned char cached_mesh_mac[MACADDRLEN];
__DRAM_IN_865X struct net_device *cached_mesh_dev = NULL;
#endif
#ifdef CLIENT_MODE
__DRAM_IN_865X unsigned char cached_sta_mac[MACADDRLEN];
__DRAM_IN_865X struct net_device *cached_sta_dev = NULL;
#endif

#if	defined(RTL_CACHED_BR_STA)
__DRAM_IN_865X unsigned char cached_br_sta_mac[MACADDRLEN];
__DRAM_IN_865X struct net_device *cached_br_sta_dev = NULL;
extern struct net_device *get_shortcut_dev(unsigned char *da);
#endif

#endif

//for 8671 IGMP snooping
#ifdef CONFIG_RTL8672
#define wlan_igmp_tag 0x1f
extern int enable_IGMP_SNP;
#ifdef CONFIG_EXT_SWITCH
extern void check_IGMP_snoop_rx(struct sk_buff *skb, int tag);
#endif
// MBSSID Port Mapping
extern struct port_map wlanDev[];
extern int g_port_mapping;
#endif

#ifdef CONFIG_RTL_STP
unsigned char STPmac[6] = { 1, 0x80, 0xc2, 0,0,0};
static struct net_device* wlan_pseudo_dev;
#define WLAN_INTERFACE_NAME			"wlan0"
#endif

#if defined(__LINUX_2_6__) && defined(CONFIG_RTK_VLAN_SUPPORT)
extern int rtk_vlan_support_enable;
#endif

#if defined(CONFIG_RTL_EAP_RELAY) || defined(CONFIG_RTK_INBAND_HOST_HACK)
extern unsigned char inband_Hostmac[]; //it's from br.c
#endif
/* ======================== RX procedure declarations ======================== */
#ifndef CONFIG_RTK_MESH
static
#endif
 void rtl8192cd_rx_mgntframe(struct rtl8192cd_priv *priv,
				struct list_head *list, struct rx_frinfo *inputPfrinfo);
static void rtl8192cd_rx_ctrlframe(struct rtl8192cd_priv *priv,
				struct list_head *list, struct rx_frinfo *inputPfrinfo);
#ifndef CONFIG_RTK_MESH
static
#endif
__MIPS16
__IRAM_IN_865X
 void rtl8192cd_rx_dataframe(struct rtl8192cd_priv *priv,
				struct list_head *list, struct rx_frinfo *inputPfrinfo);


static int auth_filter(struct rtl8192cd_priv *priv, struct stat_info *pstat,
				struct rx_frinfo *pfrinfo);
static void ctrl_handler(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo);

static void process_amsdu(struct rtl8192cd_priv *priv, struct stat_info *pstat, struct rx_frinfo *pfrinfo);


#ifndef USE_OUT_SRC
static unsigned char QueryRxPwrPercentage(signed char AntPower)
{
	if ((AntPower <= -100) || (AntPower >= 20))
		return	0;
	else if (AntPower >= 0)
		return	100;
	else
		return	(100+AntPower);
}
#endif

int SignalScaleMapping(int CurrSig)
{
	int RetSig;

	// Step 1. Scale mapping.
	if(CurrSig >= 61 && CurrSig <= 100)
	{
		RetSig = 90 + ((CurrSig - 60) / 4);
	}
	else if(CurrSig >= 41 && CurrSig <= 60)
	{
		RetSig = 78 + ((CurrSig - 40) / 2);
	}
	else if(CurrSig >= 31 && CurrSig <= 40)
	{
		RetSig = 66 + (CurrSig - 30);
	}
	else if(CurrSig >= 21 && CurrSig <= 30)
	{
		RetSig = 54 + (CurrSig - 20);
	}
	else if(CurrSig >= 5 && CurrSig <= 20)
	{
		RetSig = 42 + (((CurrSig - 5) * 2) / 3);
	}
	else if(CurrSig == 4)
	{
		RetSig = 36;
	}
	else if(CurrSig == 3)
	{
		RetSig = 27;
	}
	else if(CurrSig == 2)
	{
		RetSig = 18;
	}
	else if(CurrSig == 1)
	{
		RetSig = 9;
	}
	else
	{
		RetSig = CurrSig;
	}

	return RetSig;
}

#ifndef USE_OUT_SRC
static unsigned char EVMdbToPercentage(signed char Value)
{
	signed char ret_val;

	ret_val = Value;

	if (ret_val >= 0)
		ret_val = 0;
	if (ret_val <= -33)
		ret_val = -33;
	ret_val = 0 - ret_val;
	ret_val*=3;
	if (ret_val == 99)
		ret_val = 100;
	return(ret_val);
}
#endif


#ifdef MP_SWITCH_LNA

#define ss_threshold_H 0x28
#define ss_threshold_L 0x17

static __inline__ void dynamic_switch_lna(struct rtl8192cd_priv *priv)
{

	unsigned int tmp_b30 = PHY_QueryBBReg(priv, 0xb30, bMaskDWord);


	unsigned int tmp_dd0 = PHY_QueryBBReg(priv, 0xdd0, bMaskDWord);
	unsigned int tmp_dd0_a = (tmp_dd0 & 0x3f);
	unsigned int tmp_dd0_b = ((tmp_dd0 & 0x3f00) >> 8);

	//======= PATH  A ============

	if((tmp_dd0_a >= ss_threshold_H) && (!(tmp_b30 & BIT(21))))
	{
		if(priv->pshare->rx_packet_ss_a >= 10)
			priv->pshare->rx_packet_ss_a = 0;
		
		priv->pshare->rx_packet_ss_a = (priv->pshare->rx_packet_ss_a+1); 
		
		if(priv->pshare->rx_packet_ss_a > 3)
			priv->pshare->rx_packet_ss_a = 3; 

		if( priv->pshare->rx_packet_ss_a == 3)
		{
			tmp_b30 = (tmp_b30 | BIT(21)) ; 
			PHY_SetBBReg(priv, 0xb30, bMaskDWord, tmp_b30 );
			printk("!!!! UP 3 PACKETS !!!! PATH A dd0[0x%x] > 0x%x, Change b30 = 0x%x!!!!\n\n", 
					tmp_dd0_a , ss_threshold_H, tmp_b30 );
		}
			
	}
	else if((tmp_dd0_a <= ss_threshold_L) && (tmp_b30 & BIT(21)))
	{
		if(priv->pshare->rx_packet_ss_a < 10)
			priv->pshare->rx_packet_ss_a = 10;
		
		priv->pshare->rx_packet_ss_a = (priv->pshare->rx_packet_ss_a+1) ;
		
		if(priv->pshare->rx_packet_ss_a > 13)
			priv->pshare->rx_packet_ss_a = 13; 
		
		if(priv->pshare->rx_packet_ss_a == 13)
		{
			tmp_b30 = (tmp_b30 & ~(BIT(21))) ; 
			PHY_SetBBReg(priv, 0xb30, bMaskDWord, tmp_b30 );
			printk("!!!! UP 3 PACKETS !!!! PATH A dd0[0x%x] < 0x%x, Change b30 = 0x%x!!!!\n\n", 
					tmp_dd0_a , ss_threshold_L, tmp_b30 );

		}
	}

	//======= PATH  B ============

	if((tmp_dd0_b >= ss_threshold_H) && (!(tmp_b30 & BIT(23))))
	{
		if(priv->pshare->rx_packet_ss_b >= 10)
			priv->pshare->rx_packet_ss_b = 0;
		
		priv->pshare->rx_packet_ss_b = (priv->pshare->rx_packet_ss_b+1); 
		
		if(priv->pshare->rx_packet_ss_b > 3)
			priv->pshare->rx_packet_ss_b = 3; 

		if( priv->pshare->rx_packet_ss_b == 3)
		{
			tmp_b30 = (tmp_b30 | BIT(23)) ; 
			PHY_SetBBReg(priv, 0xb30, bMaskDWord, tmp_b30 );
			printk("!!!! UP 3 PACKETS !!!! PATH B dd0[0x%x] > 0x%x, Change b30 = 0x%x!!!!\n\n", 
					tmp_dd0_b , ss_threshold_H, tmp_b30 );
		}
			
	}
	else if((tmp_dd0_b <= ss_threshold_L) && (tmp_b30 & BIT(23)))
	{
		if(priv->pshare->rx_packet_ss_b < 10)
			priv->pshare->rx_packet_ss_b = 10;
		
		priv->pshare->rx_packet_ss_b = (priv->pshare->rx_packet_ss_b+1) ;
		
		if(priv->pshare->rx_packet_ss_b > 13)
			priv->pshare->rx_packet_ss_b = 13; 
		
		if(priv->pshare->rx_packet_ss_b == 13)
		{
			tmp_b30 = (tmp_b30 & ~(BIT(23))) ; 
			PHY_SetBBReg(priv, 0xb30, bMaskDWord, tmp_b30 );
			printk("!!!! UP 3 PACKETS !!!! PATH B dd0[0x%x] < 0x%x, Change b30 = 0x%x!!!!\n\n", 
					tmp_dd0_b , ss_threshold_L, tmp_b30 );
		}
	}

}
#endif


#ifdef USE_OUT_SRC
static __inline__ void translate_rssi_sq(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo, char rate)
{
	PODM_PHY_INFO_T		pPhyInfo= (PODM_PHY_INFO_T) &(pfrinfo->rssi);
	ODM_PACKET_INFO_T	pktinfo;
	unsigned char 		*frame = get_pframe(pfrinfo) + (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
	struct stat_info 	*pstat = get_stainfo(priv, GetAddr2Ptr(frame));

	pktinfo.Rate = rate;
	pktinfo.bPacketToSelf = 1;
	pktinfo.bPacketMatchBSSID =1;
	pktinfo.StationID = (pstat ? pstat->aid : 0);	
	
	ODM_PhyStatusQuery(ODMPTR, pPhyInfo, (u1Byte *)pfrinfo->driver_info, &pktinfo);
}
#else
static __inline__ void translate_rssi_sq(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	typedef signed char		s1Byte;
	typedef unsigned char	u1Byte;
	typedef int				s4Byte;
	typedef unsigned int	u4Byte;

	PHY_STS_OFDM_8192CD_T	*pOfdm_buf;
	PHY_STS_CCK_8192CD_T	*pCck_buf;
	u1Byte				*prxpkt;
	u1Byte				i, Max_spatial_stream, tmp_rxsnr, tmp_rxevm; //, tmp_rxrssi;
	s1Byte				rx_pwr[4], rx_pwr_all=0;
	s1Byte				rx_snrX, rx_evmX; //, rx_rssiX;
	u1Byte				EVM, PWDB_ALL;
	u4Byte				RSSI;
	u1Byte				isCCKrate=0;
#if defined(CONFIG_RTL_92C_SUPPORT) || defined(CONFIG_RTL_92D_SUPPORT)
	u1Byte				report;
#endif
	unsigned int			ofdm_max_rssi=0, ofdm_min_rssi=0xff;

	/* 2007/07/04 MH For OFDM RSSI. For high power or not. */
	//static u1Byte		check_reg824 = 0;
	//static u4Byte		reg824_bit9 = 0;

	isCCKrate = is_CCK_rate(pfrinfo->rx_rate);

	/*2007.08.30 requested by SD3 Jerry */
	if (priv->pshare->phw->check_reg824 == 0) {
		priv->pshare->phw->reg824_bit9 = PHY_QueryBBReg(priv, rFPGA0_XA_HSSIParameter2, 0x200);
		priv->pshare->phw->check_reg824 = 1;
	}

	prxpkt = (u1Byte *)pfrinfo->driver_info;

	/* Initial the cck and ofdm buffer pointer */
	pCck_buf = (PHY_STS_CCK_8192CD_T *)prxpkt;
	pOfdm_buf = (PHY_STS_OFDM_8192CD_T *)prxpkt;

	memset(&pfrinfo->rf_info, 0, sizeof(struct rf_misc_info));
	pfrinfo->rf_info.mimosq[0] = -1;
	pfrinfo->rf_info.mimosq[1] = -1;

	if (isCCKrate) {
		priv->pshare->NumQryPhyStatusCCK++;
/*
		//
		// (1)Hardware does not provide RSSI for CCK
		//
		if ((get_rf_mimo_mode(priv) == MIMO_2T4R) && (priv->pshare->rf_ft_var.cck_sel_ver == 2)) {
			for (i=RF92CD_PATH_A; i<RF92CD_PATH_MAX; i++) {
				tmp_rxrssi = pCck_buf->adc_pwdb_X[i];
				rx_rssiX = (s1Byte)(tmp_rxrssi);
				rx_rssiX /= 2;
				pfrinfo->cck_mimorssi[i] = rx_rssiX;
			}
		}
*/
		//
		// (2)PWDB, Average PWDB cacluated by hardware (for rate adaptive)
		//
#if defined(CONFIG_RTL_92C_SUPPORT) || defined(CONFIG_RTL_92D_SUPPORT)
		if (
#ifdef CONFIG_RTL_92C_SUPPORT
			(GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C) 
#endif
#ifdef CONFIG_RTL_92D_SUPPORT
#ifdef CONFIG_RTL_92C_SUPPORT
			|| 
#endif
			(GET_CHIP_VER(priv) == VERSION_8192D)
#endif
			) {
			if (!priv->pshare->phw->reg824_bit9) {
				report = pCck_buf->cck_agc_rpt & 0xc0;
				report = report>>6;

#ifdef CONFIG_RTL_92C_SUPPORT
				if ((GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)) {
					switch (report) {
					case 0x3:
						rx_pwr_all = -46 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					case 0x2:
						rx_pwr_all = -26 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					case 0x1:
						rx_pwr_all = -12 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					case 0x0:
						rx_pwr_all = 16 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					}
				} else 
#endif
				{
					switch (report) {
					//Fixed by Jacken from Bryant 2008-03-20
					//Original value is -38 , -26 , -14 , -2
					//Fixed value is -35 , -23 , -11 , 6
					case 0x3:
						rx_pwr_all = -35 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					case 0x2:
						rx_pwr_all = -23 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					case 0x1:
						rx_pwr_all = -11 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					case 0x0:
						rx_pwr_all = 8 - (pCck_buf->cck_agc_rpt & 0x3e);
						break;
					}
				}
			} else {
				report = pCck_buf->cck_agc_rpt & 0x60;
				report = report>>5;

#ifdef CONFIG_RTL_92C_SUPPORT
				if ((GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)) {
					switch (report) {
					case 0x3:
						rx_pwr_all = -46 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1) ;
						break;
					case 0x2:
						rx_pwr_all = -26 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1);
						break;
					case 0x1:
						rx_pwr_all = -12 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1) ;
						break;
					case 0x0:
						rx_pwr_all = 16 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1) ;
						break;
					}
				} else 
#endif
				{
					switch (report) {
					//Fixed by Jacken from Bryant 2008-03-20
					case 0x3:
						rx_pwr_all = -35 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1);
						break;
					case 0x2:
						rx_pwr_all = -23 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1);
						break;
					case 0x1:
						rx_pwr_all = -11 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1);
						break;
					case 0x0:
						rx_pwr_all = -8 - ((pCck_buf->cck_agc_rpt & 0x1f)<<1);
						break;
					}
				}
			}
			PWDB_ALL = QueryRxPwrPercentage(rx_pwr_all);

#ifdef CONFIG_RTL_92C_SUPPORT
			if ((GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)) {
				if (priv->pshare->rf_ft_var.use_ext_lna) {
					if (!(pCck_buf->cck_agc_rpt>>7))
						PWDB_ALL = (PWDB_ALL>94)?100:(PWDB_ALL + 6);
					else
						PWDB_ALL = (PWDB_ALL<16)?0:(PWDB_ALL -16);

					/* CCK Modification */
					if (PWDB_ALL > 25 && PWDB_ALL <= 60)
						PWDB_ALL += 6;
	/*
					else if (PWDB_ALL <= 25)
						PWDB_ALL += 8;
	*/
				} else {
					if (PWDB_ALL > 99)
						PWDB_ALL -= 8;
					else if (PWDB_ALL > 50 && PWDB_ALL <= 68)
						PWDB_ALL += 4;
				}

				pfrinfo->rssi = PWDB_ALL;
				if (priv->pshare->rf_ft_var.use_ext_lna)
					pfrinfo->rssi+=10;
			} else 
#endif
			{
				pfrinfo->rssi = PWDB_ALL;
				pfrinfo->rssi+=3;
			}

			if (pfrinfo->rssi > 100)
				pfrinfo->rssi = 100;
		}
#endif

#ifdef CONFIG_RTL_88E_SUPPORT
		if (GET_CHIP_VER(priv)==VERSION_8188E) {
			unsigned int LNA_idx = ((pCck_buf->cck_agc_rpt & 0xE0) >>5);
			unsigned int VGA_idx = (pCck_buf->cck_agc_rpt & 0x1F); 
			switch(LNA_idx) {
			case 7:
				if(VGA_idx <= 27)
					rx_pwr_all = -100 + 2*(27-VGA_idx); //VGA_idx = 27~2
				else
					rx_pwr_all = -100;
				break;
			case 6:
				rx_pwr_all = -48 + 2*(2-VGA_idx); //VGA_idx = 2~0
				break;
			case 5:
				rx_pwr_all = -42 + 2*(7-VGA_idx); //VGA_idx = 7~5
				break;
			case 4:
				rx_pwr_all = -36 + 2*(7-VGA_idx); //VGA_idx = 7~4
				break;
			case 3:
				//rx_pwr_all = -28 + 2*(7-VGA_idx); //VGA_idx = 7~0
				rx_pwr_all = -24 + 2*(7-VGA_idx); //VGA_idx = 7~0
				break;
			case 2:
				if(priv->pshare->phw->reg824_bit9)
					rx_pwr_all = -12 + 2*(5-VGA_idx); //VGA_idx = 5~0
				else
					rx_pwr_all = -6+ 2*(5-VGA_idx);
				break;
			case 1:
				rx_pwr_all = 8-2*VGA_idx;
				break;
			case 0:
				rx_pwr_all = 14-2*VGA_idx;
				break;
			default:
				printk("%s %d, CCK Exception default\n", __FUNCTION__, __LINE__);
				break;
			}
			rx_pwr_all += 6;
			PWDB_ALL = QueryRxPwrPercentage(rx_pwr_all);

			if(!priv->pshare->phw->reg824_bit9) {
				if(PWDB_ALL >= 80)
					PWDB_ALL = ((PWDB_ALL-80)<<1)+((PWDB_ALL-80)>>1)+80;
				else if((PWDB_ALL <= 78) && (PWDB_ALL >= 20))
					PWDB_ALL += 3;
				if(PWDB_ALL>100)
					PWDB_ALL = 100;
			}

			pfrinfo->rssi = PWDB_ALL;
		}
#endif

		//
		// (3) Get Signal Quality (EVM)
		//
		// if(bPacketMatchBSSID)
		{
			u1Byte SQ;

			if (pfrinfo->rssi > 40) {
				SQ = 100;
			} else {
				SQ = pCck_buf->SQ_rpt;

				if (pCck_buf->SQ_rpt > 64)
					SQ = 0;
				else if (pCck_buf->SQ_rpt < 20)
					SQ = 100;
				else
					SQ = ((64-SQ) * 100) / 44;
			}
			pfrinfo->sq = SQ;
			pfrinfo->rf_info.mimosq[0] = SQ;
		}
	} else {
		priv->pshare->NumQryPhyStatusOFDM++;
		//
		// (1)Get RSSI for HT rate
		//
		for (i=RF92CD_PATH_A; i<RF92CD_PATH_MAX; i++) {
#if defined(CONFIG_RTL_92C_SUPPORT) || defined(CONFIG_RTL_88E_SUPPORT)
			if (
#ifdef CONFIG_RTL_92C_SUPPORT
				(GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)
#endif
#ifdef CONFIG_RTL_88E_SUPPORT
#ifdef CONFIG_RTL_92C_SUPPORT
				||
#endif
				(GET_CHIP_VER(priv) == VERSION_8188E)
#endif
				)
				rx_pwr[i] = ((pOfdm_buf->trsw_gain_X[i]&0x3F)*2) - 110;
			else
#endif
				rx_pwr[i] = ((pOfdm_buf->trsw_gain_X[i]&0x3F)*2) - 106;

			//Get Rx snr value in DB
			if (priv->pshare->rf_ft_var.rssi_dump) {
				tmp_rxsnr =	pOfdm_buf->rxsnr_X[i];
				rx_snrX = (s1Byte)(tmp_rxsnr);
				rx_snrX >>= 1;
				pfrinfo->rf_info.RxSNRdB[i] = (s4Byte)rx_snrX;
			}

			/* Translate DBM to percentage. */
			RSSI = QueryRxPwrPercentage(rx_pwr[i]);
			//total_rssi += RSSI;

#if defined(CONFIG_RTL_92C_SUPPORT) || defined(CONFIG_RTL_88E_SUPPORT)
			if ((
#ifdef CONFIG_RTL_92C_SUPPORT
				(GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)
#endif
#ifdef CONFIG_RTL_88E_SUPPORT
#ifdef CONFIG_RTL_92C_SUPPORT
				||
#endif
				(GET_CHIP_VER(priv) == VERSION_8188E)
#endif
				) && (priv->pshare->rf_ft_var.use_ext_lna)) {
				if ((pOfdm_buf->trsw_gain_X[i]>>7) == 1)
					RSSI = (RSSI>94)?100:(RSSI + 6);
				else
					RSSI = (RSSI<16)?0:(RSSI -16);

				if (RSSI <= 34 && RSSI >= 4)
					RSSI -= 4;
			}
#endif

			/* Record Signal Strength for next packet */
			//if(bPacketMatchBSSID)
			{
				pfrinfo->rf_info.mimorssi[i] = (u1Byte)RSSI;
			}

#if defined(CONFIG_RTL_92C_SUPPORT) || defined(CONFIG_RTL_88E_SUPPORT)
			if (
#ifdef CONFIG_RTL_92C_SUPPORT
				(GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)
#endif
#ifdef CONFIG_RTL_88E_SUPPORT
#ifdef CONFIG_RTL_92C_SUPPORT
				||
#endif
				(GET_CHIP_VER(priv) == VERSION_8188E)
#endif
				) {
				if (RSSI > ofdm_max_rssi)
					ofdm_max_rssi = RSSI;
				if (RSSI < ofdm_min_rssi)
					ofdm_min_rssi = RSSI;
			}
#endif
		}

		//
		// (2)PWDB, Average PWDB cacluated by hardware (for rate adaptive)
		//
#if defined(CONFIG_RTL_92C_SUPPORT) || defined(CONFIG_RTL_88E_SUPPORT)
		if (
#ifdef CONFIG_RTL_92C_SUPPORT
			(GET_CHIP_VER(priv) == VERSION_8192C)||(GET_CHIP_VER(priv) == VERSION_8188C)
#endif
#ifdef CONFIG_RTL_88E_SUPPORT
#ifdef CONFIG_RTL_92C_SUPPORT
			||
#endif
			(GET_CHIP_VER(priv) == VERSION_8188E)
#endif
			) {
			if ((ofdm_max_rssi - ofdm_min_rssi) < 3)
				PWDB_ALL = ofdm_max_rssi;
			else if ((ofdm_max_rssi - ofdm_min_rssi) < 6)
				PWDB_ALL = ofdm_max_rssi - 1;
			else if ((ofdm_max_rssi - ofdm_min_rssi) < 10)
				PWDB_ALL = ofdm_max_rssi - 2;
			else
				PWDB_ALL = ofdm_max_rssi - 3;
		} else 
#endif
		{
			rx_pwr_all = (((pOfdm_buf->pwdb_all ) >> 1 )& 0x7f) -106;
			PWDB_ALL = QueryRxPwrPercentage(rx_pwr_all);
		}

		pfrinfo->rssi = PWDB_ALL;

		//
		// (3)EVM of HT rate
		//
		if ((pfrinfo->rx_rate >= 0x88) && (pfrinfo->rx_rate <= 0x8f))
			Max_spatial_stream = 2; //both spatial stream make sense
		else
			Max_spatial_stream = 1; //only spatial stream 1 makes sense

		for (i=0; i<Max_spatial_stream; i++) {
			tmp_rxevm =	pOfdm_buf->rxevm_X[i];
			rx_evmX = (s1Byte)(tmp_rxevm);

			// Do not use shift operation like "rx_evmX >>= 1" because the compilor of free build environment
			// fill most significant bit to "zero" when doing shifting operation which may change a negative
			// value to positive one, then the dbm value (which is supposed to be negative)  is not correct anymore.
			rx_evmX /= 2;	//dbm

			EVM = EVMdbToPercentage(rx_evmX);

			//if(bPacketMatchBSSID)
			{
				if (i==0) // Fill value in RFD, Get the first spatial stream only
				{
					pfrinfo->sq = (u1Byte)(EVM & 0xff);
				}
				pfrinfo->rf_info.mimosq[i] = (u1Byte)(EVM & 0xff);
			}
		}
	}
	priv->pshare->RxPWDBAve += pfrinfo->rssi;
}
#endif


#ifdef CONFIG_RTL_STP
int rtl865x_wlanIF_Init(struct net_device *dev)
{
	if (dev == NULL)
		return FALSE;
	else
	{
		wlan_pseudo_dev = dev;
		printk("init wlan pseudo dev =====> %s\n", wlan_pseudo_dev->name);
	}
	return TRUE;
}
#endif


#ifdef SUPPORT_RX_UNI2MCAST
static unsigned int check_mcastL2L3Diff(struct sk_buff *skb)
{
	unsigned int DaIpAddr;
	struct iphdr* iph = SKB_IP_HEADER(skb);

	DaIpAddr = iph->daddr;
	//printk("ip:%d, %d ,%d ,%d\n",(DaIpAddr>>24) ,(DaIpAddr<<8)>>24,(DaIpAddr<<16)>>24,(DaIpAddr<<24)>>24);

	if (((DaIpAddr & 0xFF000000) >= 0xE0000000) && ((DaIpAddr & 0xFF000000) <= 0xEF000000)) {
		if (!IP_MCAST_MAC(SKB_MAC_HEADER(skb)))
			return DaIpAddr;
	}
	return 0;
}


static void ConvertMCastIPtoMMac(unsigned int group, unsigned char *gmac)
{
	unsigned int u32tmp, tmp;
	static int i;

	u32tmp = group & 0x007FFFFF;
	gmac[0] = 0x01;
	gmac[1] = 0x00;
	gmac[2] = 0x5e;

	for (i=5; i>=3; i--) {
		tmp = u32tmp & 0xFF;
		gmac[i] = tmp;
		u32tmp >>= 8;
	}
}


static void CheckUDPandU2M(struct sk_buff *pskb)
{
	int MultiIP;

	MultiIP = check_mcastL2L3Diff(pskb);
	if (MultiIP) {
		unsigned char mactmp[6];
		ConvertMCastIPtoMMac(MultiIP, mactmp);
		//printk("%02x%02x%02x:%02x%02x%02x\n", mactmp[0],mactmp[1],mactmp[2],
		//      mactmp[3],mactmp[4],mactmp[5]);
		memcpy(SKB_MAC_HEADER(pskb), mactmp, 6);
#if defined(__LINUX_2_6__)
		/*added by qinjunjie,warning:should not remove next line*/
		pskb->pkt_type = PACKET_MULTICAST;
#endif
	}
}

static void CheckV6UDPandU2M(struct sk_buff *pskb)
{
#if defined(__LINUX_2_6__) //&& !defined(CONFIG_RTL8672)
	struct ipv6hdr *iph = (struct ipv6hdr *)(skb_mac_header(pskb) + ETH_HLEN);
	unsigned char *DDA=skb_mac_header(pskb);
#else
	struct ipv6hdr *iph = (struct ipv6hdr *)(pskb->mac.raw + ETH_HLEN);
	unsigned char *DDA=pskb->mac.raw;
#endif


	/*ip(v6) format is  multicast ip*/
	if (iph->daddr.s6_addr[0] == 0xff){

		/*mac is not ipv6 multicase mac*/
		if(!ICMPV6_MCAST_MAC(DDA) ){
			/*change mac (DA) to (ipv6 multicase mac) format by (ipv6 multicast ip)*/
			DDA[0] = 0x33;
			DDA[1] = 0x33;
			memcpy(DDA+2, &iph->daddr.s6_addr[12], 4);
		}
	}
}
#endif

#ifdef A4_STA
static void add_a4_client(struct rtl8192cd_priv *priv, struct stat_info *pstat)
{
	struct list_head *phead, *plist;
	struct stat_info *sta;

	if (!netif_running(priv->dev))
		return;

	phead = &priv->a4_sta_list;
	plist = phead->next;

	while (plist != phead) {
		sta = list_entry(plist, struct stat_info, a4_sta_list);
		if (!memcmp(sta->hwaddr, pstat->hwaddr, WLAN_ADDR_LEN)) {
			ASSERT(pstat == sta);
			break;
		}
		plist = plist->next;
	}

	if (plist == phead)
		list_add_tail(&pstat->a4_sta_list, &priv->a4_sta_list);

	pstat->state |= WIFI_A4_STA;
}
#endif

#ifdef BR_SHORTCUT
#ifdef CONFIG_RTL8672
extern struct net_device *get_eth_cached_dev(unsigned char *da);
#else
#ifdef CONFIG_RTL_819X
__inline__ struct net_device *get_eth_cached_dev(unsigned char *da)
{
	extern unsigned char cached_eth_addr[MACADDRLEN];
	extern struct net_device *cached_dev;

#ifdef BR_SHORTCUT_C2
	extern unsigned char cached_eth_addr2[MACADDRLEN];
	extern struct net_device *cached_dev2;
#endif

	if (cached_dev && !memcmp(da, cached_eth_addr, MACADDRLEN))
		return cached_dev;
#ifdef BR_SHORTCUT_C2
	else if (cached_dev2 && !memcmp(da, cached_eth_addr2, MACADDRLEN))
		return cached_dev2;
#endif
	else
		return NULL;
}
#endif
#endif
#endif

#if ((defined(CONFIG_RTK_VLAN_SUPPORT) && defined(CONFIG_RTK_VLAN_FOR_CABLE_MODEM)) || defined(MCAST2UI_REFINE))
extern struct net_device* re865x_get_netdev_by_name(const char* name);
#endif

#ifdef _SINUX_
extern int g_sc_enable_brsc;
#endif

__MIPS16
__IRAM_IN_865X
void rtl_netif_rx(struct rtl8192cd_priv *priv, struct sk_buff *pskb, struct stat_info *pstat)
{
#ifdef CLIENT_MODE
#ifdef SUPPORT_RX_UNI2MCAST
	unsigned short L3_protocol;
	unsigned char *DA_START;
#endif
#ifdef VIDEO_STREAMING_REFINE
	// for video streaming refine
	extern struct net_device *is_eth_streaming_only(struct sk_buff *skb);
	struct net_device *dev;
#endif
#endif

#ifdef PREVENT_BROADCAST_STORM
    if ((OPMODE & WIFI_AP_STATE) && ((unsigned char)pskb->data[0] == 0xff) && pstat) {
        if (pstat->rx_pkts_bc > BROADCAST_STORM_THRESHOLD) {
            priv->ext_stats.rx_data_drops++;
            DEBUG_ERR("RX DROP: Broadcast storm happened!\n");
            rtl_kfree_skb(priv, pskb, _SKB_RX_);
            return;
        }
    }
#endif

#ifdef CONFIG_RTK_VLAN_WAN_TAG
	extern int rtl865x_same_root(struct net_device *dev1,struct net_device *dev2);
#endif

#if defined(CONFIG_RTL_CUSTOM_PASSTHRU)
	if (SUCCESS==rtl_isPassthruFrame(pskb->data)) {
#ifdef CLIENT_MODE
		if(priv &&((GET_MIB(priv))->dot11OperationEntry.opmode)& WIFI_STATION_STATE)
        {
		#if defined(CONFIG_RTL_92D_SUPPORT)
		unsigned int wispWlanIndex=(passThruStatusWlan&WISP_WLAN_IDX_MASK)>>WISP_WLAN_IDX_RIGHT_SHIFT;
            if((priv==(GET_VXD_PRIV((wlan_device[wispWlanIndex].priv))))
                ||(priv == wlan_device[wispWlanIndex].priv))
		{
			pskb->dev = wlan_device[passThruWanIdx].priv->pWlanDev;
		}
            #else
            pskb->dev = wlan_device[passThruWanIdx].priv->pWlanDev;
            #endif

	}
#endif
	}
#endif

#ifdef CONFIG_RTL8672
	int k;
	// Modofied by Mason Yu
	// MBSSID Port Mapping
	if ( g_port_mapping == TRUE ) {
		for (k=0; k<5; k++) {
			if ( priv->dev == wlanDev[k].dev_pointer ) {
				pskb->vlan_member = wlanDev[k].dev_ifgrp_member;
				break;
			}
		}
	}
#endif

#ifdef GBWC
	if (priv->pmib->gbwcEntry.GBWCMode && pstat) {
		if (((priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_MAC_INNER) && (pstat->GBWC_in_group)) ||
			((priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_MAC_OUTTER) && !(pstat->GBWC_in_group)) ||
			(priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_IF_RX) ||
			(priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_IF_TRX)) {
			if ((priv->GBWC_rx_count + pskb->len) > ((priv->pmib->gbwcEntry.GBWCThrd_rx * 1024 / 8) / (100 / GBWC_TO))) {
				// over the bandwidth
				if (priv->GBWC_consuming_Q) {
					// in rtl8192cd_GBWC_timer context
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: BWC bandwidth over!\n");
					rtl_kfree_skb(priv, pskb, _SKB_RX_);
				}
				else {
					// normal Rx path
					int ret = enque(priv, &(priv->GBWC_rx_queue.head), &(priv->GBWC_rx_queue.tail),
							(unsigned int)(priv->GBWC_rx_queue.pSkb), NUM_TXPKT_QUEUE, (void *)pskb);
					if (ret == FALSE) {
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: BWC rx queue full!\n");
						rtl_kfree_skb(priv, pskb, _SKB_RX_);
					}
					else
						*(unsigned int *)&(pskb->cb[4]) = (unsigned int)pstat;	// backup pstat pointer
				}
				return;
			}
			else {
				// not over the bandwidth
				if (CIRC_CNT(priv->GBWC_rx_queue.head, priv->GBWC_rx_queue.tail, NUM_TXPKT_QUEUE) &&
						!priv->GBWC_consuming_Q) {
					// there are already packets in queue, put in queue too for order
					int ret = enque(priv, &(priv->GBWC_rx_queue.head), &(priv->GBWC_rx_queue.tail),
							(unsigned int)(priv->GBWC_rx_queue.pSkb), NUM_TXPKT_QUEUE, (void *)pskb);
					if (ret == FALSE) {
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: BWC rx queue full!\n");
						rtl_kfree_skb(priv, pskb, _SKB_RX_);
					}
					else
						*(unsigned int *)&(pskb->cb[4]) = (unsigned int)pstat;	// backup pstat pointer
					return;
				}
				else {
					// can pass up directly
					priv->GBWC_rx_count += pskb->len;
				}
			}
		}
	}
#endif

#ifdef ENABLE_RTL_SKB_STATS
	rtl_atomic_dec(&priv->rtl_rx_skb_cnt);
#endif

#ifdef	_11s_TEST_MODE_
	mesh_debug_rx1(priv, pskb);
#endif

#ifdef CONFIG_RTL_STP
	if (((unsigned char)pskb->data[12]) < 0x06)
	{
		if (!memcmp(pskb->data, STPmac, 5) && !(((unsigned char )pskb->data[5])& 0xF0))
		{
			if (memcmp(pskb->dev->name, WLAN_INTERFACE_NAME, sizeof(WLAN_INTERFACE_NAME)) == 0)
			{
				if (wlan_pseudo_dev != NULL)
					pskb->dev = wlan_pseudo_dev;
				pskb->protocol = eth_type_trans(pskb, priv->dev);
#if defined(__LINUX_2_6__) && defined(RX_TASKLET) && !defined(CONFIG_RTL8672)
					netif_receive_skb(pskb);
				#else
					netif_rx(pskb);
				#endif
			}
		}
	}
	else
#endif
	{
#ifdef CONFIG_RTK_VLAN_SUPPORT
        if (rtk_vlan_support_enable && priv->pmib->vlan.global_vlan) {
#if defined(CONFIG_RTK_VLAN_NEW_FEATURE)
                if (rx_vlan_process(priv->dev, &priv->pmib->vlan, pskb, NULL)){
#else
                if (rx_vlan_process(priv->dev, &priv->pmib->vlan, pskb)){
#endif
                        priv->ext_stats.rx_data_drops++;
                        DEBUG_ERR("RX DROP: by vlan!\n");
                        rtl_kfree_skb(priv, pskb, _SKB_RX_);
                        return;
                }

#if defined(CONFIG_RTK_VLAN_FOR_CABLE_MODEM)
                if(rtk_vlan_support_enable == 2 && pskb->tag.f.tpid ==  htons(ETH_P_8021Q))
                {
                        struct net_device *toDev;

                        toDev = re865x_get_netdev_by_name("eth1");

                        //printk("===%s(%d),vid(%d),from(%s),todev(%s),skb->tag.vid(%d)\n",__FUNCTION__,__LINE__,pskb->tag.f.pci & 0xfff, pskb->dev->name,
                                //toDev?toDev->name:NULL,pskb->tag.f.pci & 0xfff);

                        if(toDev)
                        {
                                pskb->dev = toDev;
                                toDev->netdev_ops->ndo_start_xmit(pskb,toDev);
                                return;
                        }

                }
#endif
        }
#endif

#if defined(BR_SHORTCUT)
	/*if lltd, don't go shortcut*/
	if(*(unsigned short *)(pskb->data+ETH_ALEN*2) != htons(0x88d9))
	{
		struct net_device *cached_dev=NULL;

#ifdef CONFIG_RTK_MESH

        if (pskb->dev && (pskb->dev == priv->mesh_dev))
        {
        	//chris:
        	proxy_table_chkcln(priv, pskb);
			memcpy(cached_mesh_mac, &pskb->data[6], 6);
			cached_mesh_dev = pskb->dev;
        }
#endif

#ifdef WDS
		if (pskb->dev && pskb->dev->base_addr==0) {
			if(priv->pmib->dot11WdsInfo.wdsNum>1)
				cached_wds_dev = NULL;
			else
			{
				memcpy(cached_wds_mac, &pskb->data[6], 6);
				cached_wds_dev = pskb->dev;
			}
		}
#endif

#ifdef CLIENT_MODE
		if ((OPMODE & WIFI_STATION_STATE) && pskb->dev) {
			memcpy(cached_sta_mac, &pskb->data[6], 6);
			cached_sta_dev = pskb->dev;
	
			   if (!(pskb->data[0] & 0x01) &&
				 !priv->pmib->dot11OperationEntry.disable_brsc &&
#ifdef __KERNEL__
				 (priv->dev->br_port)  &&
#endif
				 ((cached_dev=get_shortcut_dev(pskb->data)) !=NULL) 
				 && netif_running(cached_dev)){
				 pskb->dev = cached_dev;
#if !defined(__LINUX_2_6__) || defined(CONFIG_COMPAT_NET_DEV_OPS)
				 cached_dev->hard_start_xmit(pskb, cached_dev);
#else
				 cached_dev->netdev_ops->ndo_start_xmit(pskb,cached_dev);
#endif
				 return;
				 
			   }
		}
		/*AP mode side -> Client mode side*/
		if ((OPMODE & WIFI_AP_STATE) && pskb->dev) {
			if (!(pskb->data[0] & 0x01) &&
				 !priv->pmib->dot11OperationEntry.disable_brsc &&
#ifdef __KERNEL__
				 (priv->dev->br_port)  &&
#endif
				 (!memcmp(cached_sta_mac,pskb->data,6)) &&
				 (cached_sta_dev !=NULL) 
				 && netif_running(cached_sta_dev)) {
					 pskb->dev = cached_sta_dev;
#if !defined(__LINUX_2_6__) || defined(CONFIG_COMPAT_NET_DEV_OPS)
					 cached_sta_dev->hard_start_xmit(pskb, cached_sta_dev);
#else
					 cached_sta_dev->netdev_ops->ndo_start_xmit(pskb,cached_sta_dev);
#endif
					 return;
				}
		}
#endif
#ifdef WDS
		if (pskb->dev && (pskb->dev->base_addr || priv->pmib->dot11WdsInfo.wdsNum<2))
#endif
		if (!(pskb->data[0] & 0x01) &&
				!priv->pmib->dot11OperationEntry.disable_brsc &&
#if defined(CONFIG_DOMAIN_NAME_QUERY_SUPPORT) || defined(CONFIG_RTL_ULINKER)
				(pskb->data[37] != 68) && /*port 68 is dhcp dest port. In order to hack dns ip, so dhcp packa                                                            can't enter bridge short cut.*/
#endif
#ifdef __KERNEL__
#ifndef _SINUX_ // if sinux, no linux bridge, so should don't depend on br_port if use br_shortcut (John Qian 2010/6/24)
				(priv->dev->br_port) &&
#else
                (g_sc_enable_brsc) &&
#endif
#endif
#ifdef CONFIG_RTL_819X
			#if defined(CONFIG_RTL_ULINKER_BRSC)
				(((cached_dev=brsc_get_cached_dev(0, pskb->data))!=NULL) || ((cached_dev = get_eth_cached_dev(pskb->data)) != NULL))
			#else
				((cached_dev = get_eth_cached_dev(pskb->data)) != NULL) 
			#endif				
#else
				cached_dev
#endif
#ifdef CONFIG_RTK_VLAN_WAN_TAG
				&& rtl865x_same_root(pskb->dev,cached_dev)
#endif
				&&
				netif_running(cached_dev)) {

		#if defined(CONFIG_RTL_ULINKER_BRSC)
			if (cached_usb.dev && cached_dev == cached_usb.dev) {
				BRSC_COUNTER_UPDATE(tx_wlan_sc);
				BDBG_BRSC("BRSC: get shortcut dev[%s]\n", cached_usb.dev->name);
			
				if (pskb->dev)
					brsc_cache_dev(1, pskb->dev, pskb->data+ETH_ALEN);
			}
		#endif
				
#if defined(__ECOS) && defined(_DEBUG_RTL8192CD_)
			priv->ext_stats.br_cnt_sc++;
#endif
			pskb->dev = cached_dev;
#ifdef TX_SCATTER
			pskb->list_num = 0;
#endif
#if !defined(__LINUX_2_6__) || defined(CONFIG_COMPAT_NET_DEV_OPS)
				cached_dev->hard_start_xmit(pskb, cached_dev);
			#else
				cached_dev->netdev_ops->ndo_start_xmit(pskb,cached_dev);
			#endif
			return;
		}
	}
#endif // BR_SHORTCUT
	 #if defined(CONFIG_RTL_FASTBRIDGE)
	 	if (priv->dev->br_port) {
			if (RTL_FB_RETURN_SUCCESS==rtl_fb_process_in_nic(pskb, pskb->dev))
				return;
	 	}
	#endif

#ifdef MBSSID
	if ((OPMODE & WIFI_AP_STATE) && pskb->dev && (pskb->dev->base_addr != 0)) {
		*(unsigned int *)&(pskb->cb[8]) = 0x86518190;						// means from wlan interface
		*(unsigned int *)&(pskb->cb[12]) = priv->pmib->miscEntry.groupID;	// remember group ID
	}
#endif

#ifdef __KERNEL__
	if (pskb->dev)
#ifdef __LINUX_2_6__
		pskb->protocol = eth_type_trans(pskb, pskb->dev);
	else
#endif
	        pskb->protocol = eth_type_trans(pskb, priv->dev);
#endif

#ifdef CONFIG_RTL8672
	if(enable_IGMP_SNP) {
#ifdef CONFIG_EXT_SWITCH
		check_IGMP_snoop_rx(pskb, wlan_igmp_tag);
#endif
	}
	pskb->switch_port = priv->dev->name;
#endif

#ifdef  SUPPORT_RX_UNI2MCAST
	/* under sta mode for check UDP type packet that L3 IP is multicast but L2 mac is not */
	if (((OPMODE & WIFI_STATION_STATE) == WIFI_STATION_STATE))
	{
		L3_protocol = *(unsigned short *)(SKB_MAC_HEADER(pskb) + MACADDRLEN * 2);
		DA_START = SKB_MAC_HEADER(pskb);
		if( L3_protocol == __constant_htons(0x0800) )
		//&&(*(unsigned char *)(skb_mac_header(pskb) + 23)) == 0x11) { /*added by qinjunjie,warning:unicast to multicast conversion should not only limited to udp*/
		{
		    CheckUDPandU2M(pskb);
		}else if(L3_protocol == __constant_htons(0x86dd) &&
			*(unsigned char *)(SKB_MAC_HEADER(pskb) + 20) == 0x11 )
		{
			CheckV6UDPandU2M(pskb);
	    }
	}

#ifdef BR_SHORTCUT
#ifdef VIDEO_STREAMING_REFINE
	// for video streaming refine
	if ((OPMODE & WIFI_STATION_STATE) &&
#if defined(__LINUX_2_6__) && !defined(CONFIG_RTL8672)
		(*((unsigned char *)(skb_mac_header(pskb) + 0)) & 0x01) &&
#else
		(*((unsigned char *)pskb->mac.raw) & 0x01) &&
#endif
		!priv->pmib->dot11OperationEntry.disable_brsc &&
		(priv->dev->br_port) &&
		((dev = is_eth_streaming_only(pskb)) != NULL)) {
			skb_push(pskb, 14);
#if !defined(__LINUX_2_6__) || defined(CONFIG_COMPAT_NET_DEV_OPS)
			dev->hard_start_xmit(pskb, dev);
#else
			dev->netdev_ops->ndo_start_xmit(pskb, dev);
#endif
			return;
		}
#endif // VIDEO_STREAMING_REFINE
#endif // BR_SHORTCUT
#endif // SUPPORT_RX_UNI2MCAST

#if defined(__ECOS) && defined(_DEBUG_RTL8192CD_)
		priv->ext_stats.br_cnt_nosc++;
#endif

#if defined(__LINUX_2_6__) && defined(RX_TASKLET) && !defined(CONFIG_RTL8672)
		netif_receive_skb(pskb);
	#else
#if defined(__ECOS) && defined(ISR_DIRECT)
		netif_rx(pskb, wlan_device[0].priv->dev);
#else
		netif_rx(pskb);
	#endif
#endif
	}
}


#ifdef GBWC
static int GBWC_forward_check(struct rtl8192cd_priv *priv, struct sk_buff *pskb, struct stat_info *pstat)
{
	if (priv->pmib->gbwcEntry.GBWCMode && pstat) {
		if (((priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_MAC_INNER) && (pstat->GBWC_in_group)) ||
			((priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_MAC_OUTTER) && !(pstat->GBWC_in_group)) ||
			(priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_IF_RX) ||
			(priv->pmib->gbwcEntry.GBWCMode == GBWC_MODE_LIMIT_IF_TRX)) {
			if ((priv->GBWC_rx_count + pskb->len) > ((priv->pmib->gbwcEntry.GBWCThrd_rx * 1024 / 8) / (100 / GBWC_TO))) {
				// over the bandwidth
				int ret = enque(priv, &(priv->GBWC_tx_queue.head), &(priv->GBWC_tx_queue.tail),
						(unsigned int)(priv->GBWC_tx_queue.pSkb), NUM_TXPKT_QUEUE, (void *)pskb);
				if (ret == FALSE) {
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: BWC tx queue full!\n");
					dev_kfree_skb_any(pskb);
				}
				else {
#ifdef ENABLE_RTL_SKB_STATS
					rtl_atomic_inc(&priv->rtl_tx_skb_cnt);
#endif
				}
				return 1;
			}
			else {
				// not over the bandwidth
				if (CIRC_CNT(priv->GBWC_tx_queue.head, priv->GBWC_tx_queue.tail, NUM_TXPKT_QUEUE)) {
					// there are already packets in queue, put in queue too for order
					int ret = enque(priv, &(priv->GBWC_tx_queue.head), &(priv->GBWC_tx_queue.tail),
							(unsigned int)(priv->GBWC_tx_queue.pSkb), NUM_TXPKT_QUEUE, (void *)pskb);
					if (ret == FALSE) {
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: BWC tx queue full!\n");
						dev_kfree_skb_any(pskb);
					}
					else {
#ifdef ENABLE_RTL_SKB_STATS
						rtl_atomic_inc(&priv->rtl_tx_skb_cnt);
#endif
					}
					return 1;
				}
				else {
					// can forward directly
					priv->GBWC_rx_count += pskb->len;
				}
			}
		}
	}

	return 0;
}
#endif


__MIPS16
#ifndef CONFIG_ETHWAN
__IRAM_IN_865X
#endif
static void reorder_ctrl_pktout(struct rtl8192cd_priv *priv, struct sk_buff *pskb, struct stat_info *pstat)
{
	struct stat_info *dst_pstat = (struct stat_info*)(*(unsigned int *)&(pskb->cb[4]));

	if (dst_pstat == 0)
		rtl_netif_rx(priv, pskb, pstat);
	else
	{
#ifdef TX_SCATTER
		pskb->list_num = 0;
#endif
#ifdef CONFIG_RTK_MESH
	#ifdef RX_RL_SHORTCUT
		if (GET_MIB(priv)->dot1180211sInfo.mesh_enable && isMeshPoint(pstat) && isMeshPoint(dst_pstat)){
        	DECLARE_TXINSN(txinsn);
			memcpy(txinsn.nhop_11s, dst_pstat->hwaddr, MACADDRLEN);
			txinsn.is_11s = RELAY_11S;
			fire_data_frame(pskb, priv->mesh_dev, &txinsn);
		} else
	#endif
        if (rtl8192cd_start_xmit(pskb, isMeshPoint(dst_pstat) ? priv->mesh_dev: priv->dev))
#else
		if (rtl8192cd_start_xmit(pskb, priv->dev))
#endif
			rtl_kfree_skb(priv, pskb, _SKB_RX_);
	}
}


__MIPS16
#ifndef CONFIG_ETHWAN
__IRAM_IN_865X
#endif
static void reorder_ctrl_consumeQ(struct rtl8192cd_priv *priv, struct stat_info *pstat, unsigned char tid, int seg)
{
	int win_start, win_size;
	struct reorder_ctrl_entry *rc_entry;

	rc_entry  = &pstat->rc_entry[tid];
	win_start = rc_entry->win_start;
	win_size  = priv->pmib->reorderCtrlEntry.ReorderCtrlWinSz;

	while (SN_LESS(win_start, rc_entry->last_seq) || (win_start == rc_entry->last_seq)) {
		if (rc_entry->packet_q[win_start & (win_size - 1)]) {
			reorder_ctrl_pktout(priv, rc_entry->packet_q[win_start & (win_size - 1)], pstat);
			rc_entry->packet_q[win_start & (win_size - 1)] = NULL;
#ifdef _DEBUG_RTL8192CD_
			if (seg == 0)
				pstat->rx_rc_passupi++;
			else if (seg == 2)
				pstat->rx_rc_passup2++;
			else if (seg == 3)
				pstat->rx_rc_passup3++;
			else if (seg == 4)
				pstat->rx_rc_passup4++;
#endif
		}
		win_start = SN_NEXT(win_start);
	}
	rc_entry->start_rcv = FALSE;
}


__MIPS16
#ifndef CONFIG_ETHWAN
__IRAM_IN_865X
#endif
static int reorder_ctrl_timer_add(struct rtl8192cd_priv *priv, struct stat_info *pstat, int tid, int from_timeout)
{
	unsigned int now, timeout, new_timer=0;
	int setup_timer;
	int current_idx, next_idx;
	int sys_tick;

	if (!from_timeout) {
		while (CIRC_CNT(priv->pshare->rc_timer_head, priv->pshare->rc_timer_tail, RC_TIMER_NUM)) {
			if (priv->pshare->rc_timer[priv->pshare->rc_timer_tail].pstat == NULL) {
				priv->pshare->rc_timer_tail = (priv->pshare->rc_timer_tail + 1) & (RC_TIMER_NUM - 1);
			}
			else
				break;
		}

		if (CIRC_CNT(priv->pshare->rc_timer_head, priv->pshare->rc_timer_tail, RC_TIMER_NUM)) {
			timeout = priv->pshare->rc_timer[priv->pshare->rc_timer_tail].timeout;
			if (TSF_LESS(timeout, jiffies) || (timeout == jiffies)) {
				if (timer_pending(&priv->pshare->rc_sys_timer))
					del_timer_sync(&priv->pshare->rc_sys_timer);
#ifdef __ECOS
				reorder_ctrl_timeout((void *)priv);
#else
				reorder_ctrl_timeout((unsigned long)priv);
#endif
			}
		}
	}

	current_idx = priv->pshare->rc_timer_head;

	while (CIRC_CNT(current_idx, priv->pshare->rc_timer_tail, RC_TIMER_NUM)) {
		if (priv->pshare->rc_timer[priv->pshare->rc_timer_tail].pstat == NULL) {
			priv->pshare->rc_timer_tail = (priv->pshare->rc_timer_tail + 1) & (RC_TIMER_NUM - 1);
			new_timer = 1;
		}
		else
			break;
	}

	if (CIRC_CNT(current_idx, priv->pshare->rc_timer_tail, RC_TIMER_NUM) == 0) {
		if (timer_pending(&priv->pshare->rc_sys_timer))
			del_timer_sync(&priv->pshare->rc_sys_timer);
		setup_timer = 1;
	}
	else if (CIRC_SPACE(current_idx, priv->pshare->rc_timer_tail, RC_TIMER_NUM) == 0) {
		DEBUG_ERR("%s: %s, RC timer overflow!\n", priv->dev->name, __FUNCTION__ );
		return -1;
	}
	else {	// some items in timer queue
		setup_timer = 0;
		if (new_timer)
			new_timer = priv->pshare->rc_timer[priv->pshare->rc_timer_tail].timeout;
	}

	next_idx = (current_idx + 1) & (RC_TIMER_NUM - 1);

	priv->pshare->rc_timer[current_idx].priv = priv;
	priv->pshare->rc_timer[current_idx].pstat = pstat;
	priv->pshare->rc_timer[current_idx].tid = (unsigned char)tid;
	priv->pshare->rc_timer_head = next_idx;

	now = jiffies;
	timeout = now + priv->pshare->rc_timer_tick;
	priv->pshare->rc_timer[current_idx].timeout = timeout;
	sys_tick = priv->pshare->rc_timer_tick;

	if (!from_timeout) {
		if (setup_timer) {
			mod_timer(&priv->pshare->rc_sys_timer, jiffies + sys_tick);
		}
		else if (new_timer) {
			if (TSF_LESS(new_timer, now)) {
				mod_timer(&priv->pshare->rc_sys_timer, jiffies + sys_tick);
			}
			else {
				sys_tick = TSF_DIFF(new_timer, now);
				if (sys_tick < 1)
					sys_tick = 1;
				mod_timer(&priv->pshare->rc_sys_timer, jiffies + sys_tick);
			}
		}
	}

	return current_idx;
}


#ifndef CONFIG_ETHWAN
__IRAM_IN_865X
#endif
#if defined(__ECOS)
void reorder_ctrl_timeout(void *task_priv)
#elif defined(USE_WLAN_TIMER_FOR_RC) || defined(__KERNEL__)
void reorder_ctrl_timeout(unsigned long task_priv)
#endif
{
	struct rtl8192cd_priv *priv = (struct rtl8192cd_priv *)task_priv;
	unsigned int timeout, current_time;
	struct reorder_ctrl_entry *rc_entry=NULL;
	struct rtl8192cd_priv *priv_this=NULL;
	struct stat_info *pstat;
	int win_start=0, win_size, win_end, head, tid=0, sys_tick;
	unsigned long flags;

	if (!(priv->drv_state & DRV_STATE_OPEN))
		return;

#ifdef PCIE_POWER_SAVING
	if ((priv->pwr_state == L2) || (priv->pwr_state == L1))
		return ;
#endif

	SAVE_INT_AND_CLI(flags);
	SMP_LOCK(flags);

	current_time = jiffies;

	head = priv->pshare->rc_timer_head;
	win_size = priv->pmib->reorderCtrlEntry.ReorderCtrlWinSz;

	while (CIRC_CNT(head, priv->pshare->rc_timer_tail, RC_TIMER_NUM))
	{
		pstat = priv->pshare->rc_timer[priv->pshare->rc_timer_tail].pstat;
		if (pstat) {
			timeout = priv->pshare->rc_timer[priv->pshare->rc_timer_tail].timeout;
			if (TSF_LESS(timeout, current_time) || (TSF_DIFF(timeout, current_time) <= 1)) {
				priv_this = priv->pshare->rc_timer[priv->pshare->rc_timer_tail].priv;
				tid       = priv->pshare->rc_timer[priv->pshare->rc_timer_tail].tid;
				rc_entry  = &pstat->rc_entry[tid];
				win_start = rc_entry->win_start;
				win_end   = (win_start + win_size) & 0xfff;
				priv->pshare->rc_timer[priv->pshare->rc_timer_tail].pstat = NULL;
			}
			else {
				sys_tick = TSF_DIFF(timeout, current_time);
				if (sys_tick < 1)
					sys_tick = 1;
				mod_timer(&priv->pshare->rc_sys_timer, jiffies + sys_tick);

				if (TSF_LESS(timeout, current_time))
					DEBUG_ERR("Setup RC timer %d too late (now %d)\n", timeout, current_time);

				RESTORE_INT(flags);
				SMP_UNLOCK(flags);
				return;
			}
		}

		priv->pshare->rc_timer_tail = (priv->pshare->rc_timer_tail + 1) & (RC_TIMER_NUM - 1);

		if (pstat) {
			if (!(rc_entry->packet_q[win_start & (win_size - 1)]))
				win_start = SN_NEXT(win_start);

			while (rc_entry->packet_q[win_start & (win_size - 1)]) {
				reorder_ctrl_pktout(priv_this, rc_entry->packet_q[win_start & (win_size - 1)], pstat);
				rc_entry->packet_q[win_start & (win_size - 1)] = NULL;
#ifdef _DEBUG_RTL8192CD_
				pstat->rx_rc_passupi++;
#endif
				win_start = SN_NEXT(win_start);
			}

			rc_entry->win_start = win_start;

			if (SN_LESS(win_start, rc_entry->last_seq) || (win_start == rc_entry->last_seq)) {
				rc_entry->rc_timer_id = reorder_ctrl_timer_add(priv_this, pstat, tid, 1) + 1;
				if (rc_entry->rc_timer_id == 0)
					reorder_ctrl_consumeQ(priv_this, pstat, tid, 0);
			}
			else {
				rc_entry->start_rcv = FALSE;
				rc_entry->rc_timer_id = 0;
			}
		}
	}

	if (CIRC_CNT(priv->pshare->rc_timer_head, priv->pshare->rc_timer_tail, RC_TIMER_NUM)) {
		sys_tick = (priv->pshare->rc_timer[priv->pshare->rc_timer_tail].timeout - current_time);
		if (sys_tick < 1)
				sys_tick = 1;
		mod_timer(&priv->pshare->rc_sys_timer, jiffies + sys_tick);

		if (TSF_LESS(priv->pshare->rc_timer[priv->pshare->rc_timer_tail].timeout, current_time))
			DEBUG_ERR("Setup RC timer %d too late (now %d)\n", priv->pshare->rc_timer[priv->pshare->rc_timer_tail].timeout, current_time);
	}
	RESTORE_INT(flags);
	SMP_UNLOCK(flags);
}


/* ====================================================================================
   segment   1      2               3                         4
   -----------------+--------------------------------+-------------------------
                  win_start                       win_end
                    +--------------------------------+
                                  win_size

   segment 1: drop this packet
   segment 2: indicate this packet, then indicate the following packets until a hole
   segment 3: queue this packet in corrosponding position
   segment 4: indicate queued packets until SN_DIFF(seq, win_start)<win_size, then
              queue this packet
====================================================================================*/
__MIPS16
__IRAM_IN_865X
int check_win_seqment(unsigned short win_start, unsigned short win_end, unsigned short seq)
{
	if (SN_LESS(seq, win_start))
		return 1;
	else if (seq == win_start)
		return 2;
	else if (SN_LESS(win_start, seq) && SN_LESS(seq, win_end))
		return 3;
	else
		return 4;
}


__MIPS16
#ifndef CONFIG_ETHWAN
__IRAM_IN_865X
#endif
static int reorder_ctrl_check(struct rtl8192cd_priv *priv, struct stat_info *pstat, struct rx_frinfo *pfrinfo)
{
	unsigned short	seq;
	unsigned char	tid;
	int index, segment;
	int win_start, win_size, win_end;
	struct reorder_ctrl_entry *rc_entry;

	seq       = pfrinfo->seq;
	tid       = pfrinfo->tid;
	rc_entry  = &pstat->rc_entry[tid];
	win_start = rc_entry->win_start;
	win_size  = priv->pmib->reorderCtrlEntry.ReorderCtrlWinSz;
	win_end   = (win_start + win_size) & 0xfff;

	if (!pfrinfo->paggr && (rc_entry->start_rcv == FALSE))
		return TRUE;

	if (rc_entry->start_rcv == FALSE)
	{
		rc_entry->start_rcv = TRUE;
		rc_entry->win_start = SN_NEXT(seq);
		rc_entry->last_seq = seq;
		return TRUE;
	}
	else
	{
		segment = check_win_seqment(win_start, win_end, seq);
		if (segment == 1) {
#ifdef _DEBUG_RTL8192CD_
			pstat->rx_rc_drop1++;
#endif
			//priv->ext_stats.rx_data_drops++;
			//DEBUG_ERR("RX DROP: skb behind the window\n");
			//rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
			//return FALSE;
			return TRUE;
		}
		else if (segment == 2) {
			reorder_ctrl_pktout(priv, pfrinfo->pskb, pstat);
			win_start = SN_NEXT(win_start);
			while (rc_entry->packet_q[win_start & (win_size - 1)]) {
				reorder_ctrl_pktout(priv, rc_entry->packet_q[win_start & (win_size - 1)], pstat);
				rc_entry->packet_q[win_start & (win_size - 1)] = NULL;
				win_start = SN_NEXT(win_start);
#ifdef _DEBUG_RTL8192CD_
				pstat->rx_rc_passup2++;
#endif
			}
			rc_entry->win_start = win_start;
			if (SN_LESS(rc_entry->last_seq, seq))
				rc_entry->last_seq = seq;

			if (rc_entry->rc_timer_id)
				priv->pshare->rc_timer[rc_entry->rc_timer_id - 1].pstat = NULL;
			if (SN_LESS(rc_entry->last_seq, win_start))
				rc_entry->rc_timer_id = 0;
			else {
				rc_entry->rc_timer_id = reorder_ctrl_timer_add(priv, pstat, tid, 0) + 1;
				if (rc_entry->rc_timer_id == 0)
					reorder_ctrl_consumeQ(priv, pstat, tid, 2);
			}
			return FALSE;
		}
		else if (segment == 3) {
			index = seq & (win_size - 1);
			if (rc_entry->packet_q[index]) {
#ifdef _DEBUG_RTL8192CD_
				pstat->rx_rc_drop3++;
#endif
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: skb already in rc queue\n");
				rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
				return FALSE;
			}
			else {
				rc_entry->packet_q[index] = pfrinfo->pskb;
#ifdef _DEBUG_RTL8192CD_
				pstat->rx_rc_reorder3++;
#endif
			}
			if (SN_LESS(rc_entry->last_seq, seq))
				rc_entry->last_seq = seq;
			if (rc_entry->rc_timer_id == 0) {
				rc_entry->rc_timer_id = reorder_ctrl_timer_add(priv, pstat, tid, 0) + 1;
				if (rc_entry->rc_timer_id == 0)
					reorder_ctrl_consumeQ(priv, pstat, tid, 3);
			}
			return FALSE;
		}
		else {	// (segment == 4)
			while ((SN_DIFF(seq, win_start) >= win_size) || (rc_entry->packet_q[win_start & (win_size - 1)])) {
				if (rc_entry->packet_q[win_start & (win_size - 1)]) {
					reorder_ctrl_pktout(priv, rc_entry->packet_q[win_start & (win_size - 1)], pstat);
					rc_entry->packet_q[win_start & (win_size - 1)] = NULL;
#ifdef _DEBUG_RTL8192CD_
					pstat->rx_rc_passup4++;
#endif
				}
				win_start = SN_NEXT(win_start);
			}
			rc_entry->win_start = win_start;

			index = seq & (win_size - 1);
			if (rc_entry->packet_q[index]) {
#ifdef _DEBUG_RTL8192CD_
				pstat->rx_rc_drop4++;
#endif
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: skb already in rc queue\n");
				rtl_kfree_skb(priv, rc_entry->packet_q[index], _SKB_RX_);
			}

			rc_entry->packet_q[index] = pfrinfo->pskb;
#ifdef _DEBUG_RTL8192CD_
			pstat->rx_rc_reorder4++;
#endif
			rc_entry->last_seq = seq;
			if (rc_entry->rc_timer_id)
				priv->pshare->rc_timer[rc_entry->rc_timer_id - 1].pstat = NULL;
			rc_entry->rc_timer_id = reorder_ctrl_timer_add(priv, pstat, tid, 0) + 1;
			if (rc_entry->rc_timer_id == 0)
				reorder_ctrl_consumeQ(priv, pstat, tid, 4);
			return FALSE;
		}
	}
}


#ifdef RX_SHORTCUT
/*---------------------------------------------------------------
	return value:
	TRUE:	MIC ok
	FALSE:	MIC error
----------------------------------------------------------------*/
static int wait_mic_done_and_compare(unsigned char *org_mic, unsigned char *tkipmic)
{
	register unsigned long int l,r;
	int delay = 20;

	while ((*(volatile unsigned int *)GDMAISR & GDMA_COMPIP) == 0) {
		delay_us(delay);
		delay = delay / 2;
	}

	l = *(volatile unsigned int *)GDMAICVL;
	r = *(volatile unsigned int *)GDMAICVR;

	tkipmic[0] = (unsigned char)(l & 0xff);
	tkipmic[1] = (unsigned char)((l >> 8) & 0xff);
	tkipmic[2] = (unsigned char)((l >> 16) & 0xff);
	tkipmic[3] = (unsigned char)((l >> 24) & 0xff);
	tkipmic[4] = (unsigned char)(r & 0xff);
	tkipmic[5] = (unsigned char)((r >> 8) & 0xff);
	tkipmic[6] = (unsigned char)((r >> 16) & 0xff);
	tkipmic[7] = (unsigned char)((r >> 24) & 0xff);

	return (memcmp(org_mic, tkipmic, 8) ? FALSE : TRUE);
}


unsigned char rfc1042_header[WLAN_LLC_HEADER_SIZE]={0xaa,0xaa,0x03,00,00,00};

/*---------------------------------------------------------------
	return value:
	0:	shortcut ok, rx data has passup
	1:	discard this packet
	-1:	can't do shortcut, data path should be continued
 ---------------------------------------------------------------*/
__MIPS16
#ifndef CONFIG_ETHWAN
__IRAM_IN_865X
#endif
int rx_shortcut(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
//	unsigned long flags=0;
	struct stat_info *pstat, *dst_pstat;
	int privacy, tkip_mic_ret=0;
	int payload_length, offset, pos=0, do_rc=0;
	struct wlan_ethhdr_t *e_hdr;
	unsigned char rxmic[8], tkipmic[8];
	struct sk_buff skb_copy;
	unsigned char wlanhdr_copy[sizeof(struct wlanllc_hdr)];
	unsigned char *pframe = get_pframe(pfrinfo);
	unsigned char da[MACADDRLEN];
	unsigned short tpcache=0;
#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)

	unsigned char *meshHdrPtr=NULL;
	//unsigned char zeromac[6]={0x00,0x00,0x00,0x00,0x00,0x00};
	struct path_sel_entry *pEntry = NULL;
#endif
	unsigned char is_qos_datafrm=0;

	pstat = get_stainfo(priv, GetAddr2Ptr(pframe));
	is_qos_datafrm = is_qos_data(pframe);
/*
	printk("pfrinfo->is_11s = %d\n", pfrinfo->is_11s);
	//printk("pfrinfo->mesh_header.DestMACAddr = %02x\n", pfrinfo->mesh_header.DestMACAddr[5]);
	//printk("pfrinfo->mesh_header.SrcMACAddr = %02x\n", pfrinfo->mesh_header.SrcMACAddr[5]);
	printk("pstat->rx_wlanhdr.wlanhdr.meshhdr.DestMACAddr = %02x\n", pstat->rx_wlanhdr.wlanhdr.meshhdr.DestMACAddr[5]);
	printk("pstat->rx_wlanhdr.wlanhdr.meshhdr.SrcMACAddr = %02x\n", pstat->rx_wlanhdr.wlanhdr.meshhdr.SrcMACAddr[5]);
	printk("pstat->rx_payload_offset = %d\n", pstat->rx_payload_offset);
	*/

#if defined(CONFIG_RTK_MESH) && !defined(RX_RL_SHORTCUT)

	// RTK mesh doesn't support shortcut now -- chris 071909.
	if (GET_MIB(priv)->dot1180211sInfo.mesh_enable)
		return -1;
#endif

#if 0	// already flush cache in rtl8192cd_rx_isr()
#ifndef RTL8190_CACHABLE_CLUSTER
#ifdef __MIPSEB__
	pframe = (UINT8 *)((unsigned int)pframe | 0x20000000);
#endif
#endif
#endif

	if (priv->pmib->dot11OperationEntry.guest_access
#ifdef CONFIG_RTL8186_KB
			||(pstat && pstat->ieee8021x_ctrlport == DOT11_PortStatus_Guest)
#endif
		)
		return -1;

	if (pstat && (pstat->rx_payload_offset > 0) &&
		(GetFragNum(pframe) == 0) && (GetMFrag(pframe) == 0))
	{
		privacy = GetPrivacy(pframe);
        memcpy(da, pfrinfo->da, MACADDRLEN);

		tpcache = GetTupleCache(pframe);
#ifdef CLIENT_MODE
        if (IS_MCAST(da))
        {
            if (tpcache == pstat->tpcache_mcast)
            {
                priv->ext_stats.rx_decache++;
                rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
                SNMP_MIB_INC(dot11FrameDuplicateCount, 1);
                return 0;
            }
        }
        else
#endif
		if (is_qos_datafrm) {
			pos = GetSequence(pframe) & (TUPLE_WINDOW - 1);
			if (tpcache == pstat->tpcache[pfrinfo->tid][pos]) {
				priv->ext_stats.rx_decache++;
				rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
				SNMP_MIB_INC(dot11FrameDuplicateCount, 1);
				return 0;
			}
		}
		else {
			if (GetRetry(pframe)) {
				if (tpcache == pstat->tpcache_mgt) {
					priv->ext_stats.rx_decache++;
					rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
					SNMP_MIB_INC(dot11FrameDuplicateCount, 1);
					return 0;
				}
			}
		}

		// check wlan header
		if ((pstat->rx_privacy == privacy) &&
			!memcmp(GetAddr1Ptr(pframe), pstat->rx_wlanhdr.wlanhdr.addr1, 18))
		{

#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)
			extern void mesh_shortcut_update(DRV_PRIV *priv, struct rx_frinfo *pfrinfo);

			if (pfrinfo->is_11s) {

				if (memcmp(GetAddr4Ptr(pframe), pstat->rx_wlanhdr.wlanhdr.addr4, MACADDRLEN)){
					return -1;
				}
				if (memcmp(GetAddr3Ptr(pframe), GET_MY_HWADDR, MACADDRLEN)) {
					pEntry = priv->pathsel_table->search_entry(priv->pathsel_table, GetAddr3Ptr(pframe));
					if (pEntry) {
						//printk("pEntry -> nh %02x \n", pEntry->nexthopMAC[5]);
					}
					else
						return -1;
				}


				meshHdrPtr = getMeshHeader(priv, priv->pmib->dot11sKeysTable.dot11Privacy, pframe);
				if( meshHdrPtr )
				{
					if((int)(*(meshHdrPtr+1)) == 0)	// *(meshHdrPtr+1)) = TTL; filter packets due to duplicate
						return -1;


					memcpy(&(pfrinfo->mesh_header), meshHdrPtr, sizeof(struct lls_mesh_header) );

				}
				else {
					return -1;
				}


				if (( pfrinfo->mesh_header.mesh_flag)&1) //only shorcut 6 address
				{
					if (memcmp(pfrinfo->mesh_header.DestMACAddr, pstat->rx_wlanhdr.wlanhdr.meshhdr.DestMACAddr, 6) ||
						memcmp(pfrinfo->mesh_header.SrcMACAddr, pstat->rx_wlanhdr.wlanhdr.meshhdr.SrcMACAddr, 6)) {
							return -1;
						}
				} else {
					return -1;
				}

				mesh_shortcut_update(priv, pfrinfo);
				pfrinfo->pskb->dev = priv->mesh_dev;
			}
			else

#endif //CONFIG_RTK_MESH

#ifdef WDS
			if (pfrinfo->to_fr_ds == 3
	#ifdef CONFIG_RTK_MESH
				&& priv->pmib->dot11WdsInfo.wdsEnabled
	#endif
				) {
				if (memcmp(GetAddr4Ptr(pframe), pstat->rx_wlanhdr.wlanhdr.addr4, 6))
					return -1;
				pfrinfo->pskb->dev = getWdsDevByAddr(priv, GetAddr2Ptr(pframe));
			}
			else
#endif

#ifdef A4_STA
			if (pfrinfo->to_fr_ds == 3 && (pstat->state & WIFI_A4_STA)) {
				if (memcmp(GetAddr4Ptr(pframe), pstat->rx_wlanhdr.wlanhdr.addr4, 6))
					return -1;
				a4_sta_add(priv, pstat, GetAddr4Ptr(pframe));
			}
#endif
				pfrinfo->pskb->dev = priv->dev;

			offset = pstat->rx_payload_offset + sizeof(rfc1042_header);

/*
			printk("pstat->rx_payload_offset = %02x, 0xaa, cmp = %d \n",pframe[pstat->rx_payload_offset],
				memcmp(&pframe[pstat->rx_payload_offset], rfc1042_header, 6));
			printk("pstat->rx_ethhdr.type = %02x, pframe[offset] = %02x, cmp= %d \n", pstat->rx_ethhdr.type, pframe[offset],
				memcmp(&pstat->rx_ethhdr.type,&pframe[offset], 2));
*/
			// check snap header
			if (memcmp(&pframe[pstat->rx_payload_offset], rfc1042_header, 6) ||
				memcmp(&pstat->rx_ethhdr.type,&pframe[offset], 2))
				return -1;

			payload_length = pfrinfo->pktlen - offset - pstat->rx_trim_pad - 2;
			if (payload_length < WLAN_ETHHDR_LEN)
				return -1;

			if (privacy)
			{

#ifdef WDS
				if (pfrinfo->to_fr_ds == 3
#ifdef CONFIG_RTK_MESH
					&& priv->pmib->dot11WdsInfo.wdsEnabled
#endif
				)
					privacy = priv->pmib->dot11WdsInfo.wdsPrivacy;

				else
#endif
					privacy = get_sta_encrypt_algthm(priv, pstat);

#ifdef CONFIG_RTL_WAPI_SUPPORT
				if (privacy==_WAPI_SMS4_)
				{
					/*	Decryption	*/
					if (SecSWSMS4Decryption(priv, pstat, pfrinfo) == FAIL)
					{
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: WAPI decrpt error!\n");
						priv->ext_stats.rx_data_drops++;
						rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
						return 1;
					}
					pframe = get_pframe(pfrinfo);
				}
				else
#endif
				{
					if (privacy == _TKIP_PRIVACY_)
					{
						memcpy((void *)rxmic, (void *)(pframe + pfrinfo->pktlen - 8 - 4), 8); // 8 michael, 4 icv
						//SAVE_INT_AND_CLI(flags);
						tkip_mic_ret = tkip_rx_mic(priv, pframe, pfrinfo->da, pfrinfo->sa,
							pfrinfo->tid, pframe + pfrinfo->hdr_len + 8,
							pfrinfo->pktlen - pfrinfo->hdr_len - 8 - 8 - 4, tkipmic, 1); // 8 IV, 8 Mic, 4 ICV
						if (tkip_mic_ret) { // MIC completed
							//RESTORE_INT(flags);
							if (memcmp(rxmic, tkipmic, 8)) {
								return -1;
							}
						}
						else {
							memcpy(&skb_copy, pfrinfo->pskb, sizeof(skb_copy));
							memcpy(wlanhdr_copy, pframe, sizeof(wlanhdr_copy));
						}
					}
				}
			}

			if ((priv->pmib->dot11BssType.net_work_type & WIRELESS_11N) &&
				priv->pmib->reorderCtrlEntry.ReorderCtrlEnable) {
				if (!IS_MCAST(GetAddr1Ptr(pframe)))
					do_rc = 1;
			}

			rx_sum_up(NULL, pstat, pfrinfo->pktlen, 0);
#ifdef USE_OUT_SRC	
			priv->pshare->NumRxBytesUnicast += pfrinfo->pktlen;
#endif			
			update_sta_rssi(priv, pstat, pfrinfo);

#ifdef DETECT_STA_EXISTANCE
#ifdef CONFIG_RTL_88E_SUPPORT
			if (GET_CHIP_VER(priv)==VERSION_8188E) {
				if (pstat->leave!= 0)
					RTL8188E_MACID_NOLINK(priv, 0, REMAP_AID(pstat));
	        }
#endif
			pstat->leave = 0;
#endif

			//printk("RXSC\n");

#ifdef SUPPORT_SNMP_MIB
			if (IS_MCAST(da))
				SNMP_MIB_INC(dot11MulticastReceivedFrameCount, 1);
#endif

			/* chop 802.11 header from skb. */
			//skb_put(pfrinfo->pskb, pfrinfo->pktlen);	// pskb->tail will be wrong
			pfrinfo->pskb->tail = pfrinfo->pskb->data + pfrinfo->pktlen;
			pfrinfo->pskb->len = pfrinfo->pktlen;

			skb_pull(pfrinfo->pskb, offset+2);

#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)

			if (pfrinfo->is_11s && (pEntry==NULL)) {
				skb_pull(pfrinfo->pskb, 16); //because we guarantee only 6 address frame enters shortcut
				payload_length -= 16;
			}
#endif

			e_hdr = (struct wlan_ethhdr_t *)skb_push(pfrinfo->pskb, WLAN_ETHHDR_LEN);
			memcpy((unsigned char *)e_hdr, (unsigned char *)&pstat->rx_ethhdr, sizeof(struct wlan_ethhdr_t));
			/* chop off the 802.11 CRC */

			skb_trim(pfrinfo->pskb, payload_length + WLAN_ETHHDR_LEN);


			//printk("RXSC skb_pull offset+2 = %d\n", offset+2);
			//printk("RXSC pstat->rx_ethhdr dest= %02x\n", e_hdr->daddr[5]);
			//printk("RXSC pstat->rx_ethhdr src = %02x\n", e_hdr->saddr[5]);

			if ((privacy == _TKIP_PRIVACY_) && (tkip_mic_ret == FALSE)) {
				if (wait_mic_done_and_compare(rxmic, tkipmic) == FALSE) {
//					RESTORE_INT(flags);
					memcpy(pfrinfo->pskb, &skb_copy, sizeof(skb_copy));
					memcpy(pframe, wlanhdr_copy, sizeof(wlanhdr_copy));
					return -1;
				}
//				RESTORE_INT(flags);
			}

#ifdef CLIENT_MODE
			if (IS_MCAST(da))
				pstat->tpcache_mcast = tpcache;
			else
#endif
			if (is_qos_datafrm)
				pstat->tpcache[pfrinfo->tid][pos] = tpcache;
			else
				pstat->tpcache_mgt = tpcache;

			if (OPMODE & WIFI_AP_STATE)
			{

// Button 2009.7.31
#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)
				if (pfrinfo->is_11s )
						dst_pstat = (pEntry) ? get_stainfo(priv, pEntry->nexthopMAC) : 0;
				else
#endif
				dst_pstat = get_stainfo(priv, da);

#ifdef A4_STA
				if (priv->pshare->rf_ft_var.a4_enable && (dst_pstat == NULL))
					dst_pstat = a4_sta_lookup(priv, da);
#endif

#if defined(CONFIG_RTK_MESH) ||  defined(WDS)
				if ((pfrinfo->to_fr_ds==3) ||
					(dst_pstat == NULL) || !(dst_pstat->state & WIFI_ASOC_STATE))
#else
				if ((dst_pstat == NULL) || (!(dst_pstat->state & WIFI_ASOC_STATE)))
#endif
				{

// Button 2009.7.31
#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)
					if (pfrinfo->is_11s && (pEntry!=NULL)) {
						//chris 080409
						DECLARE_TXINSN(txinsn);
						memcpy(txinsn.nhop_11s, pEntry->nexthopMAC, MACADDRLEN);
						(int)(*(meshHdrPtr+1))--;
						txinsn.is_11s = RELAY_11S;
						if (do_rc) {
							*(unsigned int *)&(pfrinfo->pskb->cb[4]) = dst_pstat;
							if (reorder_ctrl_check(priv, pstat, pfrinfo)) {
								fire_data_frame(pfrinfo->pskb, priv->mesh_dev, &txinsn);
							}
						} else {
							fire_data_frame(pfrinfo->pskb, priv->mesh_dev, &txinsn);
						}
					} else
#endif
					if (do_rc) {
						*(unsigned int *)&(pfrinfo->pskb->cb[4]) = 0;
						if (reorder_ctrl_check(priv, pstat, pfrinfo)) {
							rtl_netif_rx(priv, pfrinfo->pskb, pstat);
						}
					}
					else
						rtl_netif_rx(priv, pfrinfo->pskb, pstat);
				}
				else
				{
					if (priv->pmib->dot11OperationEntry.block_relay == 1) {
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: Relay unicast packet is blocked in shortcut!\n");
						rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
						return 1;
					}
					else if (priv->pmib->dot11OperationEntry.block_relay == 2) {
						DEBUG_INFO("Relay unicast packet is blocked! Indicate to bridge in shortcut\n");
						rtl_netif_rx(priv, pfrinfo->pskb, pstat);
					}
					else {
#ifdef ENABLE_RTL_SKB_STATS
						rtl_atomic_dec(&priv->rtl_rx_skb_cnt);
#endif
#ifdef GBWC
						if (GBWC_forward_check(priv, pfrinfo->pskb, pstat)) {
							// packet is queued, nothing to do
						}
						else
#endif
						if (do_rc) {
							*(unsigned int *)&(pfrinfo->pskb->cb[4]) = (unsigned int)dst_pstat;	// backup pstat pointer
							if (reorder_ctrl_check(priv, pstat, pfrinfo)) {
#ifdef TX_SCATTER
								pfrinfo->pskb->list_num = 0;
#endif
#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)
								if (rtl8192cd_start_xmit(pfrinfo->pskb, isMeshPoint(dst_pstat)? priv->mesh_dev: priv->dev))
#else
								if (rtl8192cd_start_xmit(pfrinfo->pskb, priv->dev))
#endif
									rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
							}
						}
						else {
#ifdef TX_SCATTER
							pfrinfo->pskb->list_num = 0;
#endif
#if defined(CONFIG_RTK_MESH) && defined(RX_RL_SHORTCUT)
							if (rtl8192cd_start_xmit(pfrinfo->pskb, isMeshPoint(dst_pstat)? priv->mesh_dev: priv->dev))
#else
							if (rtl8192cd_start_xmit(pfrinfo->pskb, priv->dev))
#endif
								rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
						}
					}
				}
				return 0;
			}
#ifdef CLIENT_MODE
			else if (OPMODE & (WIFI_STATION_STATE | WIFI_ADHOC_STATE)) {
				priv->rxDataNumInPeriod++;
				if (IS_MCAST(pfrinfo->pskb->data)) {
					priv->rxMlcstDataNumInPeriod++;
				} else if ((OPMODE & WIFI_STATION_STATE) && (priv->ps_state)) {
					if ((GetFrameSubType(pframe) == WIFI_DATA)
#ifdef WIFI_WMM
						||(QOS_ENABLE && pstat->QosEnabled && (GetFrameSubType(pframe) == WIFI_QOS_DATA))
#endif
						) {
						if (GetMData(pframe)) {
#if defined(WIFI_WMM) && defined(WMM_APSD)
							if (QOS_ENABLE && APSD_ENABLE && priv->uapsd_assoc) {
								if (!((priv->pmib->dot11QosEntry.UAPSD_AC_BE && ((pfrinfo->tid == 0) || (pfrinfo->tid == 3))) ||
									(priv->pmib->dot11QosEntry.UAPSD_AC_BK && ((pfrinfo->tid == 1) || (pfrinfo->tid == 2))) ||
									(priv->pmib->dot11QosEntry.UAPSD_AC_VI && ((pfrinfo->tid == 4) || (pfrinfo->tid == 5))) ||
									(priv->pmib->dot11QosEntry.UAPSD_AC_VO && ((pfrinfo->tid == 6) || (pfrinfo->tid == 7)))))
									issue_PsPoll(priv);
							} else
#endif
							{
								issue_PsPoll(priv);
							}
						}
					}
				}

#ifdef RTK_BR_EXT
				if (!priv->pmib->ethBrExtInfo.nat25sc_disable &&
						!(pfrinfo->pskb->data[0] & 1) &&
						*((unsigned short *)(pfrinfo->pskb->data+ETH_ALEN*2)) == __constant_htons(ETH_P_IP) &&
							!memcmp(priv->scdb_ip, pfrinfo->pskb->data+ETH_HLEN+16, 4))
					memcpy(pfrinfo->pskb->data, priv->scdb_mac, ETH_ALEN);
				else
				if(nat25_handle_frame(priv, pfrinfo->pskb) == -1) {
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: nat25_handle_frame fail in shortcut!\n");
					rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
					return 1;
				}
#endif
				if (do_rc) {
					*(unsigned int *)&(pfrinfo->pskb->cb[4]) = 0;
					if (reorder_ctrl_check(priv, pstat, pfrinfo))
						rtl_netif_rx(priv, pfrinfo->pskb, pstat);
				}
				else
					rtl_netif_rx(priv, pfrinfo->pskb, pstat);
				return 0;
			}
#endif
			else
			{
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: Non supported mode in process_datafrme in shortcut\n");
				rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
				return 1;
			}
		}
	}
	return -1;
}
#endif // RX_SHORTCUT


#ifdef DRVMAC_LB
void lb_convert(struct rtl8192cd_priv *priv, unsigned char *pframe)
{
	unsigned char *addr1, *addr2, *addr3;

	if (get_tofr_ds(pframe) == 0x01)
	{
		SetToDs(pframe);
		ClearFrDs(pframe);
		addr1 = GetAddr1Ptr(pframe);
		addr2 = GetAddr2Ptr(pframe);
		addr3 = GetAddr3Ptr(pframe);
		if (addr1[0] & 0x01) {
			memcpy(addr3, addr1, 6);
			memcpy(addr1, addr2, 6);
			memcpy(addr2, priv->pmib->miscEntry.lb_da, 6);
		}
		else {
			memcpy(addr1, addr2, 6);
			memcpy(addr2, priv->pmib->miscEntry.lb_da, 6);
		}
	}
}
#endif


/*
	Strip from "validate_mpdu()"

	0:	no reuse, allocate new skb due to the current is queued.
	1:	reuse! due to error pkt or short pkt.
*/
static int rtl8192cd_rx_procCtrlPkt(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo
#ifdef MBSSID
				,int vap_idx
#endif
				)
{
	unsigned char *pframe = get_pframe(pfrinfo);

	if (((GetFrameSubType(pframe)) != WIFI_PSPOLL) ||
			(pfrinfo->to_fr_ds != 0x00))
		return 1;

#ifdef MBSSID
	if (GET_ROOT(priv)->pmib->miscEntry.vap_enable && (vap_idx >= 0))
	{
		priv = priv->pvap_priv[vap_idx];
		if (!(OPMODE & WIFI_AP_STATE))
			return 1;
	}
	else
#endif
	{
		if (!(OPMODE & WIFI_AP_STATE)) {
#ifdef UNIVERSAL_REPEATER
			if (IS_DRV_OPEN(GET_VXD_PRIV(priv)))
				priv = GET_VXD_PRIV(priv);
			else
				return 1;
#else
			return 1;
#endif
		}
	}

	if (!IS_BSSID(priv, GetAddr1Ptr(pframe)))
		return 1;

	// check power save state
	if (get_stainfo(priv, GetAddr2Ptr(pframe)) != NULL)
		pwr_state(priv, pfrinfo);
	else
		return 1;

#ifdef RTL8190_DIRECT_RX
	rtl8192cd_rx_ctrlframe(priv, NULL, pfrinfo);
#else
	list_add_tail(&(pfrinfo->rx_list), &(priv->rx_ctrllist));
#endif
	return 0;
}


static int rtl8192cd_rx_procNullPkt(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo
#ifdef MBSSID
				,int vap_idx
#endif
				)
{
	unsigned char *sa = pfrinfo->sa;
	struct stat_info *pstat = get_stainfo(priv, sa);
	unsigned char *pframe = get_pframe(pfrinfo);

#ifdef UNIVERSAL_REPEATER
	if ((pstat == NULL) && IS_DRV_OPEN(GET_VXD_PRIV(priv))) {
		pstat = get_stainfo(GET_VXD_PRIV(priv), sa);
		if (pstat)
			priv = GET_VXD_PRIV(priv);
	}
#endif

#ifdef MBSSID
	if ((pstat == NULL)
		&& GET_ROOT(priv)->pmib->miscEntry.vap_enable
		&& (vap_idx >= 0)) {
		pstat = get_stainfo(priv->pvap_priv[vap_idx], sa);
		if (pstat)
			priv = priv->pvap_priv[vap_idx];
	}
#endif

	if (pstat && (pstat->state & WIFI_ASOC_STATE)) {
#ifdef DRVMAC_LB
		rx_sum_up(priv, pstat, pfrinfo->pktlen, 0);
#else
		rx_sum_up(NULL, pstat, pfrinfo->pktlen, 0);
#endif
		update_sta_rssi(priv, pstat, pfrinfo);

#ifdef DETECT_STA_EXISTANCE
#ifdef CONFIG_RTL_88E_SUPPORT
        if (GET_CHIP_VER(priv)==VERSION_8188E) {
            if (pstat->leave!= 0)
                RTL8188E_MACID_NOLINK(priv, 0, REMAP_AID(pstat));
        }
#endif
		pstat->leave = 0;
#endif

		// check power save state
#ifndef DRVMAC_LB
		if (OPMODE & WIFI_AP_STATE) {
			if (IS_BSSID(priv, GetAddr1Ptr(pframe)))
				pwr_state(priv, pfrinfo);
		}
#endif
	}

#if defined(WIFI_WMM) && defined(WMM_APSD)
	if ((QOS_ENABLE)
#ifndef DRVMAC_LB
		&& (APSD_ENABLE)
#endif
		&& (OPMODE & WIFI_AP_STATE) && pstat
#ifndef DRVMAC_LB
		&& (pstat->state & WIFI_SLEEP_STATE)
#endif
		&& (pstat->QosEnabled)
#ifndef DRVMAC_LB
		&& (pstat->apsd_bitmap & 0x0f)
#endif
		&& ((GetFrameSubType(pframe)) == (WIFI_DATA_NULL | BIT(7)))) {
		unsigned char *bssid = GetAddr1Ptr(pframe);
		if (IS_BSSID(priv, bssid))
		{
#ifdef RTL8190_DIRECT_RX
			rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
			list_add_tail(&(pfrinfo->rx_list), &(priv->rx_datalist));
#endif
			return 0;
		}
	}
#endif

        /*special case for mobile wifi. the mobile insleep  will send 
         null data to AP after AP's reboot. if not deauthed, the client will not reconnect*/
        if(pstat == NULL) {
                issue_deauth(priv,GetAddr2Ptr(pframe),_RSON_UNSPECIFIED_);
        }
	// for AR5007 IOT ISSUE
	if ((!GetPwrMgt(pframe)) && (GetTupleCache(pframe) == 0) // because this is special case for AR5007, so use GetTupleCache with Seq-Num and Frag-Num, GetSequenceis also ok
		 && (OPMODE & WIFI_AP_STATE) && (IS_BSSID(priv, GetAddr1Ptr(pframe))))
	{
#ifdef RTL8190_DIRECT_RX
		rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
		list_add_tail(&(pfrinfo->rx_list), &(priv->rx_datalist));
#endif
		DEBUG_INFO("special Null Data in%d \n", pfrinfo->tid);
		return 0;
	}

	return 1;
}


// for AR5007 IOT ISSUE
static void rtl8192cd_rx_handle_Spec_Null_Data(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	unsigned char *sa;
	struct stat_info *pstat;
	unsigned char *pframe = get_pframe(pfrinfo);
	unsigned long flags;

	SAVE_INT_AND_CLI(flags);
	sa = pfrinfo->sa;
	pstat = get_stainfo(priv, sa);
	if (pstat==NULL) {
		goto out;
	}

	pframe = get_pframe(pfrinfo);
	if ((!GetPwrMgt(pframe)) && (GetTupleCache(pframe) == 0) // because this is special case for AR5007, so use GetTupleCache with Seq-Num and Frag-Num, GetSequenceis also ok
		 && (OPMODE & WIFI_AP_STATE) && (IS_BSSID(priv, GetAddr1Ptr(pframe))))
	{
		int i,j;

		DEBUG_INFO("tpcache should be reset: %d\n", pfrinfo->tid);
		for (i=0; i<8; i++)
			for (j=0; j<TUPLE_WINDOW; j++)
				pstat->tpcache[i][j] = 0xffff;
	}
out:
	RESTORE_INT(flags);
}


/*
	Check the "to_fr_ds" field:

						FromDS = 0
						ToDS = 0
*/
static int rtl8192cd_rx_dispatch_mgmt_adhoc(struct rtl8192cd_priv **priv_p, struct rx_frinfo *pfrinfo
#ifdef MBSSID
				,int vap_idx
#endif
				)
{
	int reuse = 1;
	struct rtl8192cd_priv *priv = *priv_p;
	unsigned int opmode = OPMODE;
	unsigned char *pframe = get_pframe(pfrinfo);
	int retry=GetRetry(pframe);/*in case of skb has been freed*/
	unsigned int frtype = GetFrameType(pframe);
	unsigned char *da = pfrinfo->da;
	unsigned char *bssid = GetAddr3Ptr(pframe);
	unsigned short frame_type = GetFrameSubType(pframe);
#ifdef MBSSID
	int i;
#endif

	if ((GetMFrag(pframe)) && (frtype == WIFI_MGT_TYPE))
		goto out;

#if defined(UNIVERSAL_REPEATER) || defined(MBSSID)
	// If mgt packet & (beacon or prob-rsp), put in root interface Q
	//		then it will be handled by root & virtual interface
	// If mgt packet & (prob-req), put in AP interface Q
	// If mgt packet & (others), check BSSID (addr3) for matched interface

	if (frtype == WIFI_MGT_TYPE) {
		int vxd_interface_ready=1, vap_interface_ready=0;

#ifdef UNIVERSAL_REPEATER
		if (!IS_DRV_OPEN(GET_VXD_PRIV(priv)) ||
			((opmode & WIFI_STATION_STATE) && !(GET_VXD_PRIV(priv)->drv_state & DRV_STATE_VXD_AP_STARTED)))
			vxd_interface_ready = 0;
#endif

#ifdef MBSSID
		if (opmode & WIFI_AP_STATE) {
			if (GET_ROOT(priv)->pmib->miscEntry.vap_enable) {
				for (i=0; i<RTL8192CD_NUM_VWLAN; i++) {
					if (IS_DRV_OPEN(priv->pvap_priv[i]))
						vap_interface_ready = 1;
				}
			}
		}
#endif

		if (!vxd_interface_ready && !vap_interface_ready)
			goto put_in_que;

		if (frame_type == WIFI_BEACON || frame_type == WIFI_PROBERSP) {
			pfrinfo->is_br_mgnt = 1;
			goto put_in_que;
		}

		if (frame_type == WIFI_PROBEREQ) {
#ifdef MBSSID
			if (GET_ROOT(priv)->pmib->miscEntry.vap_enable)
			{
				if (vap_interface_ready) {
					pfrinfo->is_br_mgnt = 1;
					goto put_in_que;
				}
			}
#endif
#ifdef UNIVERSAL_REPEATER
			if (opmode & WIFI_STATION_STATE) {
				if (!vxd_interface_ready) goto out;
				priv = GET_VXD_PRIV(priv);
				opmode = OPMODE;
				goto put_in_que;
			}
#endif
		}
		else { // not (Beacon, Probe-rsp, probe-rsp)
#ifdef MBSSID
			if (GET_ROOT(priv)->pmib->miscEntry.vap_enable)
			{
				if (vap_idx >= 0) {
					priv = priv->pvap_priv[vap_idx];
					goto put_in_que;
				}
			}
#endif
#ifdef UNIVERSAL_REPEATER
			if (!memcmp(GET_VXD_PRIV(priv)->pmib->dot11Bss.bssid, bssid, MACADDRLEN)) {
				if (!vxd_interface_ready) goto out;
				priv = GET_VXD_PRIV(priv);
				opmode = OPMODE;
			}
#endif
		}
	}
put_in_que:
#endif // UNIVERSAL_REPEATER || MBSSID

	if (opmode & WIFI_AP_STATE)
	{
		if (IS_MCAST(da))
		{
			// For AP mode, if DA == MCAST, then BSSID should be also MCAST
			if (IS_MCAST(bssid))
				reuse = 0;

			// support 11A
			else if (priv->pmib->dot11BssType.net_work_type & (WIRELESS_11G|WIRELESS_11A))
				reuse = 0;

#ifdef WDS
			else if (priv->pmib->dot11WdsInfo.wdsEnabled)
				reuse = 0;
#endif

#ifdef CONFIG_RTK_MESH
			else if (GET_MIB(priv)->dot1180211sInfo.mesh_enable)
				reuse = 0;
#endif

			else if (opmode & WIFI_SITE_MONITOR)
				reuse = 0;

#if defined(UNIVERSAL_REPEATER) || defined(MBSSID)
			else if (pfrinfo->is_br_mgnt && reuse)
				reuse = 0;
#endif

			else
				{}
		}
		else
		{
			/*
			 *	For AP mode, if DA is non-MCAST, then it must be BSSID, and bssid == BSSID
			 *	Action frame is an exception (for bcm iot), do not check bssid
			 */
			if (IS_BSSID(priv, da) && ((IS_BSSID(priv, bssid) || (frame_type == WIFI_WMM_ACTION))
#ifdef CONFIG_RTK_MESH // 802.11s Draft 1.0: page 11, Line 19~20
						// wildcard bssid is also acceptable -- chris
					|| (GET_MIB(priv)->dot1180211sInfo.mesh_enable
							&& (!memcmp(bssid, "\x0\x0\x0\x0\x0\x0", 6) || !memcmp(bssid, "\xff\xff\xff\xff\xff\xff", 6))
							&& (frtype == WIFI_MGT_TYPE))
#endif
			))
				reuse = 0;

			else if (opmode & WIFI_SITE_MONITOR)
				reuse = 0;

#if defined(UNIVERSAL_REPEATER) || defined(MBSSID)
			else if (pfrinfo->is_br_mgnt && reuse)
				reuse = 0;
#endif
#ifdef WDS
			else if (priv->pmib->dot11WdsInfo.wdsEnabled && priv->pmib->dot11WdsInfo.wdsNum)
				reuse = 0;
#endif

			else
				{}
		}

		if (!reuse) {
			if (frtype == WIFI_MGT_TYPE)
			{
#ifdef RTL8190_DIRECT_RX
				rtl8192cd_rx_mgntframe(priv, NULL, pfrinfo);
#else
				list_add_tail(&(pfrinfo->rx_list), &(priv->rx_mgtlist));
#endif
			}
			else
				reuse = 1;
		}
	}
#ifdef CLIENT_MODE
	else if (opmode & WIFI_STATION_STATE)
	{
		// For Station mode, sa and bssid should always be BSSID, and DA is my mac-address
		// in case of to_fr_ds = 0x00, then it must be mgt frame type

		unsigned char *myhwaddr = priv->pmib->dot11OperationEntry.hwaddr;
		if (IS_MCAST(da) || !memcmp(da, myhwaddr, MACADDRLEN))
			reuse = 0;

		if (!reuse) {
			if (frtype == WIFI_MGT_TYPE)
			{
#ifdef RTL8190_DIRECT_RX
				rtl8192cd_rx_mgntframe(priv, NULL, pfrinfo);
#else
				list_add_tail(&(pfrinfo->rx_list), &(priv->rx_mgtlist));
#endif
			}
			else
				reuse = 1;
		}
	}
	else if (opmode & WIFI_ADHOC_STATE)
	{
		unsigned char *myhwaddr = priv->pmib->dot11OperationEntry.hwaddr;
		if (IS_MCAST(da) || !memcmp(da, myhwaddr, MACADDRLEN))
		{
			if (frtype == WIFI_MGT_TYPE)
			{
#ifdef RTL8190_DIRECT_RX
				rtl8192cd_rx_mgntframe(priv, NULL, pfrinfo);
#else
				list_add_tail(&(pfrinfo->rx_list), &(priv->rx_mgtlist));
#endif
				reuse = 0;
			}
			else
			{	// data frames
				if (memcmp(bssid, "\x0\x0\x0\x0\x0\x0", MACADDRLEN) &&
						memcmp(BSSID, "\x0\x0\x0\x0\x0\x0", MACADDRLEN) &&
						!memcmp(bssid, BSSID, MACADDRLEN))
				{
#ifdef RTL8190_DIRECT_RX
					rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
					list_add_tail(&(pfrinfo->rx_list), &(priv->rx_datalist));
#endif
					reuse = 0;
				}
			}
		}
	}
#endif // CLIENT_MODE
	else
		reuse = 1;

out:

	/* update priv's point */
	*priv_p = priv;
	rx_sum_up(priv, NULL, pfrinfo->pktlen, retry);
	return reuse;
}


/*
	Check the "to_fr_ds" field:

						FromDS != 0
						ToDS = 0
*/
#if defined(CLIENT_MODE) || defined(CONFIG_RTK_MESH)
static int rtl8192cd_rx_dispatch_fromDs(struct rtl8192cd_priv **priv_p, struct rx_frinfo *pfrinfo)
{
	int reuse = 1;
	struct rtl8192cd_priv *priv = *priv_p;
	unsigned int opmode = OPMODE;
	unsigned char *pframe = get_pframe(pfrinfo);
	int retry=GetRetry(pframe);/*in case of skb has been freed*/
	unsigned int frtype = GetFrameType(pframe);
	unsigned char *da = pfrinfo->da;
	unsigned char *myhwaddr = priv->pmib->dot11OperationEntry.hwaddr;
	unsigned char *bssid = GetAddr2Ptr(pframe);

#ifdef CONFIG_RTK_MESH
	struct stat_info	*pstat = get_stainfo(priv, pfrinfo->sa);

	if (((opmode & WIFI_AP_STATE) && (GET_MIB(priv)->dot1180211sInfo.mesh_enable))
		&& ((pstat == NULL) || isPossibleNeighbor(pstat)))
		goto out;
#endif	// CONFIG_RTK_MESH

#ifdef CLIENT_MODE	 //(add for Mesh)
	if (frtype == WIFI_MGT_TYPE)
		goto out;

	if ((opmode & (WIFI_STATION_STATE | WIFI_ASOC_STATE)) ==
				  (WIFI_STATION_STATE | WIFI_ASOC_STATE))
	{
		// For Station mode,
		// da should be for me, bssid should be BSSID
		if (IS_BSSID(priv, bssid)) {
			if (IS_MCAST(da) || !memcmp(da, myhwaddr, MACADDRLEN))
			{
#ifdef RTL8190_DIRECT_RX
				rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
				list_add_tail(&(pfrinfo->rx_list), &(priv->rx_datalist));
#endif
				reuse = 0;
			}
		}
	}
#ifdef UNIVERSAL_REPEATER
	else if ((opmode & WIFI_AP_STATE) && IS_DRV_OPEN(GET_VXD_PRIV(priv)))
	{
		if (IS_BSSID(GET_VXD_PRIV(priv), bssid)) {
			if (IS_MCAST(da) || !memcmp(da, myhwaddr, MACADDRLEN))
			{
				priv = GET_VXD_PRIV(priv);
#ifdef RTL8190_DIRECT_RX
				rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
				list_add_tail(&(pfrinfo->rx_list), &priv->rx_datalist);
#endif
				reuse = 0;
			}
		}
	}
#endif
	else
		reuse = 1;

#endif	// CLIENT_MODE (add for Mesh)
out:

	/* update priv's point */
	*priv_p = priv;
	rx_sum_up(priv, NULL, pfrinfo->pktlen, retry);
	return reuse;
}
#endif	// defined(CLIENT_MODE) || defined(CONFIG_RTK_MESH)


/*
	Check the "to_fr_ds" field:

						FromDS = 0
						ToDS != 0
*/
static inline int rtl8192cd_rx_dispatch_toDs(struct rtl8192cd_priv **priv_p, 	struct rx_frinfo *pfrinfo
#ifdef MBSSID
				,int vap_idx
#endif
				)
{
	int reuse = 1;
	struct rtl8192cd_priv *priv = *priv_p;
	unsigned int opmode = OPMODE;
	unsigned char *pframe = get_pframe(pfrinfo);
	int retry=GetRetry(pframe);	/*in case of skb has been freed*/
	unsigned int frtype = GetFrameType(pframe);
	unsigned char *bssid = GetAddr1Ptr(pframe);

#ifdef CONFIG_RTK_MESH
    if (GET_MIB(priv)->dot1180211sInfo.mesh_enable == 1) {    // For MBSSID enable and STA connect to Virtual-AP can't use problem.
		struct stat_info	*pstat = get_stainfo(priv, pfrinfo->sa);

		if ((pstat == NULL) || isPossibleNeighbor(pstat))
			goto out;
    }
#endif	// CONFIG_RTK_MESH


	if (frtype == WIFI_MGT_TYPE)
		goto out;

	if (opmode & WIFI_AP_STATE)
	{
#ifdef MBSSID
		if (GET_ROOT(priv)->pmib->miscEntry.vap_enable && (vap_idx >= 0))
		{
			priv = priv->pvap_priv[vap_idx];
#ifdef RTL8190_DIRECT_RX
			rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
			list_add_tail(&(pfrinfo->rx_list), &(priv->rx_datalist));
#endif
			reuse = 0;
		}
		else
#endif
		// For AP mode, the data frame should have bssid==BSSID. (sa state checked laterly)
		if (IS_BSSID(priv, bssid))
		{
			// we only receive mgt frame when we are in SITE_MONITOR or link_loss
			// please consider the case: re-auth and re-assoc
#ifdef RTL8190_DIRECT_RX
			rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
			list_add_tail(&(pfrinfo->rx_list), &(priv->rx_datalist));
#endif
			reuse = 0;
		}
	}
#ifdef UNIVERSAL_REPEATER
	else if ((opmode & WIFI_STATION_STATE) && IS_DRV_OPEN(GET_VXD_PRIV(priv))
			&& (GET_VXD_PRIV(priv)->drv_state & DRV_STATE_VXD_AP_STARTED)) {
		if (IS_BSSID(GET_VXD_PRIV(priv), bssid))
		{
			priv = GET_VXD_PRIV(priv);
#ifdef RTL8190_DIRECT_RX
			rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
			list_add_tail(&(pfrinfo->rx_list), &priv->rx_datalist);
#endif
			reuse = 0;
		}
	}
#endif
	else
		reuse = 1;

out:

	/* update priv's point */
	*priv_p = priv;
	rx_sum_up(priv, NULL, pfrinfo->pktlen,retry);
	return reuse;
}


/*
	Check the "to_fr_ds" field:

						FromDS != 0
						ToDS != 0
	NOTE: The functuion duplicate to rtl8190_rx_dispatch_mesh (mesh_rx.c)
*/
#ifdef WDS
static int rtl8192cd_rx_dispatch_wds(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	int reuse = 1;
	unsigned char *pframe = get_pframe(pfrinfo);
	unsigned int frtype = GetFrameType(pframe);
	struct net_device *dev;
	int fragnum;

	rx_sum_up(priv, NULL, pfrinfo->pktlen, GetRetry(pframe));

	if (memcmp(GET_MY_HWADDR, GetAddr1Ptr(pframe), MACADDRLEN)) {
		DEBUG_INFO("Rx a WDS packet but which addr1 is not matched own, drop it!\n");
		goto out;
	}

	if (!priv->pmib->dot11WdsInfo.wdsEnabled) {
		DEBUG_ERR("Rx a WDS packet but WDS is not enabled in local mib, drop it!\n");
		goto out;
	}
	dev = getWdsDevByAddr(priv, GetAddr2Ptr(pframe));
	if (dev==NULL) {
#ifdef LAZY_WDS
		if (priv->pmib->dot11WdsInfo.wdsEnabled == WDS_LAZY_ENABLE) {
			if (add_wds_entry(priv, 0, GetAddr2Ptr(pframe))) {
				dev = getWdsDevByAddr(priv, GetAddr2Ptr(pframe));
				if (dev == NULL) {
					DEBUG_ERR("Rx a WDS packet but which TA is not valid, drop it!\n");
					goto out;
				}
				LOG_MSG("Add a wds entry - %02X:%02X:%02X:%02X:%02X:%02X\n",
					*GetAddr2Ptr(pframe), *(GetAddr2Ptr(pframe)+1), *(GetAddr2Ptr(pframe)+2), *(GetAddr2Ptr(pframe)+3),
					*(GetAddr2Ptr(pframe)+4), *(GetAddr2Ptr(pframe)+5));
			}
			else {
				DEBUG_ERR("Rx a WDS packet but wds table is full, drop it!\n");
				goto out;
			}
		}
		else
#endif
		{
					DEBUG_ERR("Rx a WDS packet but which TA is not valid, drop it!\n");
					goto out;
				}
	}
	if (!netif_running(dev)) {
		DEBUG_ERR("Rx a WDS packet but which interface is not up, drop it!\n");
		goto out;
	}
	fragnum = GetFragNum(pframe);
	if (GetMFrag(pframe) || fragnum) {
		DEBUG_ERR("Rx a fragment WDS packet, drop it!\n");
		goto out;
	}
	if (frtype != WIFI_DATA_TYPE) {
		DEBUG_ERR("Rx a WDS packet but which type is not DATA, drop it!\n");
		goto out;
	}
#ifdef __LINUX_2_6__
	pfrinfo->pskb->dev=dev;
#endif

#ifdef RTL8190_DIRECT_RX
	rtl8192cd_rx_dataframe(priv, NULL, pfrinfo);
#else
	list_add_tail(&pfrinfo->rx_list, &priv->rx_datalist);
#endif
	reuse = 0;
	goto out;

out:

	return reuse;
}
#endif	// WDS


/*---------------------------------------------------------------
	return value:
	0:	no reuse, allocate new skb due to the current is queued.
	1:	reuse! due to error pkt or short pkt.
----------------------------------------------------------------*/
__MIPS16
__IRAM_IN_865X
int validate_mpdu(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	unsigned char	*sa, *da, *myhwaddr, *pframe;
	unsigned int	frtype;


	int reuse = 1;
#ifdef MBSSID
	unsigned int opmode = OPMODE;
	int i, vap_idx=-1;
#endif

#ifdef __LINUX_2_6__
	if(pfrinfo == NULL || pfrinfo->pskb==NULL || pfrinfo->pskb->data == NULL)
		return 1;
#endif
	pframe = get_pframe(pfrinfo);

#if 0	// already flush cache in rtl8192cd_rx_isr()
#ifndef RTL8190_CACHABLE_CLUSTER
#ifdef __MIPSEB__
	pframe = (UINT8 *)((unsigned int)pframe | 0x20000000);
#endif
#endif
#endif

#ifdef DRVMAC_LB
	if (priv->pmib->miscEntry.drvmac_lb)
		lb_convert(priv, pframe);
#endif

	pfrinfo->hdr_len =  get_hdrlen(priv, pframe);
	if (pfrinfo->hdr_len == 0)	// unallowed packet type
	{
//		printk("pfrinfo->hdr_len == 0\n");
		return 1;
	}

	frtype = GetFrameType(pframe);
	pfrinfo->to_fr_ds = get_tofr_ds(pframe);
	pfrinfo->da	= da = 	get_da(pframe);
	pfrinfo->sa	= sa =  get_sa(pframe);
	pfrinfo->seq = GetSequence(pframe);

#ifdef CONFIG_RTK_MESH
	pfrinfo->is_11s = 0;

	// WIFI_11S_MESH_ACTION use QoS bit, but it's not a QoS data (8186 compatible)
	if (is_qos_data(pframe) && GetFrameSubType(pframe)!=WIFI_11S_MESH_ACTION) {
#else
	if (is_qos_data(pframe)) {
#endif
		pfrinfo->tid = GetTid(pframe) & 0x07;	// we only process TC of 0~7
	}
	else {
		pfrinfo->tid = 0;
	}

	myhwaddr = priv->pmib->dot11OperationEntry.hwaddr;

#ifdef	PCIE_POWER_SAVING
	if (!memcmp(myhwaddr, GetAddr1Ptr(pframe), MACADDRLEN))
		mod_timer(&priv->ps_timer, jiffies + POWER_DOWN_T0);
#endif

	// filter packets that SA is myself
	if (!memcmp(myhwaddr, sa, MACADDRLEN)
#ifdef CONFIG_RTK_MESH
        // for e.g.,  MPP1 - THIS_DEVICE - ROOT ;
        //            THIS_DEVICE use ROOT first, and then ROOT dispatch the packet to MPP1
		&& (priv->pmib->dot1180211sInfo.mesh_enable ? ((pfrinfo->to_fr_ds != 0x03) && GetFrameSubType(pframe) != WIFI_11S_MESH) : TRUE)
#endif
	)
		return 1;

#ifdef MBSSID
	if (opmode & WIFI_AP_STATE) {
		if (GET_ROOT(priv)->pmib->miscEntry.vap_enable) {
			for (i=0; i<RTL8192CD_NUM_VWLAN; i++) {
				if (IS_DRV_OPEN(priv->pvap_priv[i])) {
					if (((pfrinfo->to_fr_ds == 0x00) || (pfrinfo->to_fr_ds == 0x02)) &&
						!memcmp(priv->pvap_priv[i]->pmib->dot11StationConfigEntry.dot11Bssid, GetAddr1Ptr(pframe), MACADDRLEN)) {
						vap_idx = i;
						break;
					}
				}
			}
		}
	}
#endif

#if 0
	// check power save state
	if ((opmode & WIFI_AP_STATE)
#ifdef UNIVERSAL_REPEATER
		|| ((opmode & WIFI_STATION_STATE) && IS_DRV_OPEN(GET_VXD_PRIV(priv)))
#endif
		)
	{
		bssid = GetAddr1Ptr(pframe);

#ifdef MBSSID
		if (vap_idx >= 0) {
			if (IS_BSSID(priv->pvap_priv[vap_idx], bssid))
				pwr_state(priv->pvap_priv[vap_idx], pfrinfo);
		}
		else
#endif
#ifdef UNIVERSAL_REPEATER
		if (opmode & WIFI_AP_STATE) {
			if (IS_BSSID(priv, bssid))
				pwr_state(priv, pfrinfo);
		}
		else {
			if (IS_BSSID(GET_VXD_PRIV(priv), bssid))
				pwr_state(GET_VXD_PRIV(priv), pfrinfo);
		}
#else
		if (IS_BSSID(priv, bssid))
			pwr_state(priv, pfrinfo);
#endif
	}
#endif

	// if receiving control frames, we just handle PS-Poll only
	if (frtype == WIFI_CTRL_TYPE)
	{
		return rtl8192cd_rx_procCtrlPkt(priv, pfrinfo
#ifdef MBSSID
									  ,vap_idx
#endif
									  );
	}

	// for ap, we have handled; for station/ad-hoc, we reject null_data frame
	if (((GetFrameSubType(pframe)) == WIFI_DATA_NULL) ||
		(((GetFrameSubType(pframe)) == WIFI_DATA) && pfrinfo->pktlen <= 24)
#ifdef WIFI_WMM
		||((QOS_ENABLE) && ((GetFrameSubType(pframe)) == (WIFI_DATA_NULL | BIT(7))))
#endif
	)
	{
		return rtl8192cd_rx_procNullPkt(priv, pfrinfo
#ifdef MBSSID
									  ,vap_idx
#endif
									  );
	}

	// david, move rx statistics from rtl8192cd_rx_isr() to here because repeater issue
	//qinjunjie:move rx_sum_up to rtl8192cd_rx_dispatch_mgmt_adhoc/rtl8192cd_rx_dispatch_fromDs/rtl8192cd_rx_dispatch_toDs/rtl8192cd_rx_dispatch_wds
	//	rx_sum_up(priv, NULL, pfrinfo->pktlen, GetRetry(pframe));

	// enqueue data frames and management frames
	switch (pfrinfo->to_fr_ds)
	{
		case 0x00:	// ToDs=0, FromDs=0
			reuse = rtl8192cd_rx_dispatch_mgmt_adhoc(&priv, pfrinfo
#ifdef MBSSID
												   ,vap_idx
#endif
												   );
			break;

		case 0x01:	// ToDs=0, FromDs=1
#if defined(CLIENT_MODE) || defined(CONFIG_RTK_MESH)
			reuse = rtl8192cd_rx_dispatch_fromDs(&priv, pfrinfo);
#endif
			break;

		case 0x02:	// ToDs=1, FromDs=0
			reuse = rtl8192cd_rx_dispatch_toDs(&priv, pfrinfo
#ifdef MBSSID
											 ,vap_idx
#endif
											 );
			break;

		case 0x03:	// ToDs=1, FromDs=1
#ifdef CONFIG_RTK_MESH
			if( 1 == GET_MIB(priv)->dot1180211sInfo.mesh_enable)
			{
				reuse = rx_dispatch_mesh(priv, pfrinfo);
				break;
			}
#endif

#ifdef A4_STA
			if (priv->pshare->rf_ft_var.a4_enable) {

				reuse = rtl8192cd_rx_dispatch_toDs(&priv, pfrinfo
#ifdef MBSSID
											 ,vap_idx
#endif
											 );
				break;
			}
#endif

#ifdef WDS
			reuse = rtl8192cd_rx_dispatch_wds(priv, pfrinfo);
#endif
			break;
	}

	return reuse;
}


static void rx_pkt_exception(struct rtl8192cd_priv *priv, unsigned int cmd)
{
	struct net_device_stats *pnet_stats = &(priv->net_stats);

	if (cmd & RX_CRC32)
	{
		pnet_stats->rx_crc_errors++;
		pnet_stats->rx_errors++;
		SNMP_MIB_INC(dot11FCSErrorCount, 1);
#ifdef	_11s_TEST_MODE_
		mesh_debug_rx2(priv, cmd);
#endif
	}
	else if (cmd & RX_ICVERR)
	{
		pnet_stats->rx_errors++;
		SNMP_MIB_ASSIGN(dot11WEPICVErrorCount, 1);
	}
	else
	{
	}
}


#if defined(RX_TASKLET) || defined(__ECOS)
__IRAM_IN_865X
void rtl8192cd_rx_tkl_isr(unsigned long task_priv)
{
	struct rtl8192cd_priv *priv;
#if defined(SMP_SYNC) || defined(__ECOS)
	unsigned long x;
#endif
	priv = (struct rtl8192cd_priv *)task_priv;

//	printk("=====>> INSIDE rtl8192cd_rx_tkl_isr <<=====\n");

#ifdef PCIE_POWER_SAVING
	if ((priv->pwr_state == L2) || (priv->pwr_state == L1)) {
		return;
	}
#endif

#if defined(__LINUX_2_6__) || defined(__ECOS)
	SMP_LOCK(x);
	//RTL_W32(HIMR, 0);
#else
	SAVE_INT_AND_CLI(x);
	SMP_LOCK(x);
#ifdef CONFIG_RTL_88E_SUPPORT
	if (GET_CHIP_VER(priv)==VERSION_8188E) {
		RTL_W32(REG_88E_HIMR, priv->pshare->InterruptMask);
		RTL_W32(REG_88E_HIMRE, priv->pshare->InterruptMaskExt);
	} else
#endif
	{
		//RTL_W32(HIMR, RTL_R32(HIMR) | (HIMR_RXFOVW| HIMR_RDU | HIMR_ROK));
		RTL_W32(HIMR, RTL_R32(HIMR) | (HIMR_RXFOVW | HIMR_ROK));
	}
#endif

#ifdef DELAY_REFILL_RX_BUF
	priv->pshare->has_triggered_rx_tasklet = 2; // indicate as ISR in service
#endif

	rtl8192cd_rx_isr(priv);

	priv->pshare->has_triggered_rx_tasklet = 0;

#if defined(__LINUX_2_6__) || defined(__ECOS)
#ifdef CONFIG_RTL_88E_SUPPORT
	if (GET_CHIP_VER(priv)==VERSION_8188E) {
		RTL_W32(REG_88E_HIMR, priv->pshare->InterruptMask);
		RTL_W32(REG_88E_HIMRE, priv->pshare->InterruptMaskExt);
	} else
#endif
	{
		RTL_W32(HIMR, priv->pshare->InterruptMask);
	}
	SMP_UNLOCK(x);
#else
	RESTORE_INT(x);
	SMP_UNLOCK(x);
#endif
}
#endif


#ifdef DELAY_REFILL_RX_BUF
#ifndef __LINUX_2_6__
__MIPS16
#endif
#ifndef __ECOS
__IRAM_IN_865X
#endif
int refill_rx_ring(struct rtl8192cd_priv *priv, struct sk_buff *skb, unsigned char *data)
{
	struct rtl8192cd_hw *phw=GET_HW(priv);
	struct sk_buff *new_skb;
#if defined(__LINUX_2_6__) && defined(RX_TASKLET)
	unsigned long x;
#endif
	extern struct sk_buff *dev_alloc_8190_skb(unsigned char *data, int size);
#if defined(__LINUX_2_6__) && defined(RX_TASKLET)
	SAVE_INT_AND_CLI(x);
	SMP_LOCK_SKB(x);
#endif
	if (skb ||
			//(priv->pshare->has_triggered_rx_tasklet != 2 && phw->cur_rx_refill != phw->cur_rx)) {
			  phw->cur_rx_refill != phw->cur_rx) {
		if (skb)
			new_skb = skb;
		else {
			new_skb = dev_alloc_8190_skb(data, RX_BUF_LEN);
			if (new_skb == NULL) {
				DEBUG_ERR("out of skb struct!\n");
#if defined(__LINUX_2_6__) && defined(RX_TASKLET)
				RESTORE_INT(x);
				SMP_UNLOCK_SKB(x);
#endif
				return 0;
			}
		}

		init_rxdesc(new_skb, phw->cur_rx_refill, priv);
		//phw->cur_rx_refill = (phw->cur_rx_refill + 1) % NUM_RX_DESC;
		phw->cur_rx_refill = ((phw->cur_rx_refill+1)==NUM_RX_DESC)?0:phw->cur_rx_refill+1;
#ifdef CONFIG_RTL_88E_SUPPORT
		if (GET_CHIP_VER(priv)==VERSION_8188E) {
			RTL_W32(REG_88E_HISR, HIMR_88E_RDU);
			RTL_W32(REG_88E_HISRE, HIMRE_88E_RXFOVW);
		} else
#endif
		{
			RTL_W32(HISR,(HIMR_RXFOVW | HIMR_RDU));
		}
#if defined(__LINUX_2_6__) && defined(RX_TASKLET)
		RESTORE_INT(x);
		SMP_UNLOCK_SKB(x);
#endif
		return 1;
	}
	else {
#if defined(__LINUX_2_6__) && defined(RX_TASKLET)
		RESTORE_INT(x);
		SMP_UNLOCK_SKB(x);
#endif
		return 0;
	}
}
#endif

#ifdef RX_BUFFER_GATHER
void flush_rx_list(struct rtl8192cd_priv *priv)
{
	struct rx_frinfo *pfrinfo;
	struct list_head *phead, *plist;
	unsigned long flags;

	SAVE_INT_AND_CLI(flags);

	phead = &priv->pshare->gather_list;
	if (!phead) {
		RESTORE_INT(flags);
		return;
	}

	plist = phead->next;
	while (plist != phead) {
		pfrinfo = list_entry(plist, struct rx_frinfo, rx_list);
		if (pfrinfo->pskb)
			rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
		plist = plist->next;
	}
	INIT_LIST_HEAD(&priv->pshare->gather_list);

	RESTORE_INT(flags);
}

static int search_first_segment(struct rtl8192cd_priv *priv, struct rx_frinfo **found)
{
	struct rx_frinfo *pfrinfo, *first = NULL;
	struct list_head *phead, *plist;
	int len = 0, look_for = GATHER_FIRST, ok = 0;
	unsigned long flags;
	unsigned char *pframe;
	u8 qosControl[4];

	SAVE_INT_AND_CLI(flags);

	phead = &priv->pshare->gather_list;
	if (phead)
		plist = phead->next;

	while (phead && plist != phead) {
		pfrinfo = list_entry(plist, struct rx_frinfo, rx_list);
		plist = plist->next;

		if (pfrinfo->gather_flag & look_for) {
			len += pfrinfo->pktlen;
			if (pfrinfo->gather_flag & GATHER_FIRST) {
				first = pfrinfo;
				list_del_init(&pfrinfo->rx_list);

				if (pfrinfo->pskb) {
					pframe = get_pframe(pfrinfo);
					memcpy(qosControl, GetQosControl(pframe), 2);
					if (!is_qos_data(pframe)  || !(qosControl[0] & BIT(7)))  /* not AMSDU */
						rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
					else
						look_for = GATHER_MIDDLE | GATHER_LAST;
				}
			}
			else if (pfrinfo->gather_flag & GATHER_LAST) {
 				first->gather_len = len;
				*found = first;
				ok = 1;
				break;
			}
		}
		else {
			list_del_init(&pfrinfo->rx_list);
			if (pfrinfo->pskb)
				rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
		}
	}

	if (first && !ok) {
		if (first->pskb)
			rtl_kfree_skb(priv, first->pskb, _SKB_RX_);
	}

	RESTORE_INT(flags);
	return ok;
}

#endif /* RX_BUFFER_GATHER */

#if (defined(__ECOS) ||defined(__LINUX_2_6__)) && defined(RX_TASKLET)
#define	RTL_WLAN_RX_ATOMIC_PROTECT_ENTER	do {SAVE_INT_AND_CLI(x);} while(0)
#define	RTL_WLAN_RX_ATOMIC_PROTECT_EXIT		do {RESTORE_INT(x);} while(0)
#else
#define	RTL_WLAN_RX_ATOMIC_PROTECT_ENTER
#define	RTL_WLAN_RX_ATOMIC_PROTECT_EXIT
#endif

#ifdef CONFIG_RTK_VOIP_QOS
extern int ( *check_voip_channel_loading )( void );
#endif
#ifndef __ECOS
#ifndef __LINUX_2_6__
__MIPS16
#endif
#endif
__IRAM_IN_865X
void rtl8192cd_rx_isr(struct rtl8192cd_priv *priv)
{
	struct rx_desc *pdesc, *prxdesc;
	struct rtl8192cd_hw *phw;
	struct sk_buff *pskb, *new_skb;
	struct rx_frinfo *pfrinfo;
	unsigned int tail;
	unsigned int cmd, reuse;
#ifdef DELAY_REFILL_RX_BUF
	unsigned int cmp_flags;
#endif
	unsigned int rtl8192cd_ICV, privacy;
	struct stat_info *pstat;
#if  (defined(__ECOS) ||defined(__LINUX_2_6__)) && defined(RX_TASKLET)
	unsigned long x;
#endif
#if defined(DELAY_REFILL_RX_BUF)
	int refill;
#endif
#if defined(MP_TEST)
	unsigned char *sa,*da,*bssid;
	char *pframe;
	unsigned int  find_flag;
#endif
#if defined(CONFIG_RTL8190_PRIV_SKB)
#ifdef CONCURRENT_MODE
	extern int skb_free_num[];
#else
	extern int skb_free_num;
#endif
#endif
#if defined(RX_BUFFER_GATHER)
	int pfrinfo_update;
#endif

#if /*defined(__ECOS) ||*/ (defined (CONFIG_RTK_VOIP_QOS) && !defined (CONFIG_RTK_VOIP_ETHERNET_DSP_IS_HOST))
	unsigned long start_time = jiffies;
	int n = 0;
#endif
	if (!(priv->drv_state & DRV_STATE_OPEN))
		return;

	phw = GET_HW(priv);
	tail = phw->cur_rx;

#if defined(RTL8190_CACHABLE_DESC)
	prxdesc = (struct rx_desc *)(phw->rx_descL);
	rtl_cache_sync_wback(priv, (unsigned int)prxdesc, sizeof(struct rx_desc), PCI_DMA_FROMDEVICE);
#else
#if defined(__MIPSEB__)
	prxdesc = (struct rx_desc *)((unsigned int)(phw->rx_descL) | 0x20000000);
#else
	prxdesc = (struct rx_desc *)(phw->rx_descL);
#endif
#endif	/* RTL8190_CACHABLE_DESC */

	while (1)
	{
#if 0 //def __ECOS
		if ((n++ > 100) || ((jiffies - start_time) >= 1))
		{
			break;
		}
#endif
#if defined (CONFIG_RTK_VOIP_QOS) && !defined (CONFIG_RTK_VOIP_ETHERNET_DSP_IS_HOST)	
		if ( (n++ > 100 || (jiffies - start_time) >= 1 )&& (check_voip_channel_loading && (check_voip_channel_loading() > 0)) )
		{
			break;		
		}
#endif
#if defined(DELAY_REFILL_RX_BUF)
		refill = 1;

		/*
		if (((tail+1) % NUM_RX_DESC) == phw->cur_rx_refill) {
			break;
		}*/
		RTL_WLAN_RX_ATOMIC_PROTECT_ENTER;
		cmp_flags = ( ((tail+1)==NUM_RX_DESC?0:tail+1) == phw->cur_rx_refill );
		RTL_WLAN_RX_ATOMIC_PROTECT_EXIT;

		if (cmp_flags) {
			break;
		}
#endif

#if defined(RX_BUFFER_GATHER)
		pfrinfo_update = 0;
#endif

		pdesc = prxdesc + tail;
		cmd = get_desc(pdesc->Dword0);
		reuse = 1;

		if (cmd & RX_OWN)
			break;

		pskb = (struct sk_buff *)(phw->rx_infoL[tail].pbuf);
		pfrinfo = get_pfrinfo(pskb);

#ifdef MP_SWITCH_LNA
		if((GET_CHIP_VER(priv) == VERSION_8192D) && priv->pshare->rf_ft_var.mp_specific)
			dynamic_switch_lna(priv);
#endif

		if (cmd & RX_CRC32) {
			/*printk("CRC32 happens~!!\n");*/
			rx_pkt_exception(priv, cmd);
			goto rx_reuse;
		}
#if defined(CONFIG_RTL_88E_SUPPORT) && defined(TXREPORT)
		else if ((GET_CHIP_VER(priv)==VERSION_8188E) && 
			(((get_desc(pdesc->Dword3) >> RXdesc_88E_RptSelSHIFT) & RXdesc_88E_RptSelMask) == 2)) {
			pfrinfo->pktlen = (cmd & RX_PktLenMask);
			if (get_desc(pdesc->Dword4) || get_desc(pdesc->Dword5))
#ifdef RATEADAPTIVE_BY_ODM
				ODM_RA_TxRPT2Handle_8188E(ODMPTR, pskb->data, pfrinfo->pktlen, get_desc(pdesc->Dword4), get_desc(pdesc->Dword5));
#else
				RTL8188E_TxReportHandler(priv, pskb, get_desc(pdesc->Dword4), get_desc(pdesc->Dword5), pdesc);
#endif
			goto rx_reuse;
		} else if ((GET_CHIP_VER(priv)==VERSION_8188E) && 
			((get_desc(pdesc->Dword3) >> RXdesc_88E_RptSelSHIFT) & RXdesc_88E_RptSelMask)) {
			printk("%s %d, Rx report select mismatch, val:%d\n", __FUNCTION__, __LINE__, 
				((get_desc(pdesc->Dword3) >> RXdesc_88E_RptSelSHIFT) & RXdesc_88E_RptSelMask));
			goto rx_reuse;
		}
#endif
#if !defined(RX_BUFFER_GATHER)
		else if ((cmd & (RX_FirstSeg | RX_LastSeg)) != (RX_FirstSeg | RX_LastSeg)) {
			// h/w use more than 1 rx descriptor to receive a packet
			// that means this packet is too large
			// drop such kind of packet
			goto rx_reuse;
		}
#endif
		else if (!IS_DRV_OPEN(priv)) {
			goto rx_reuse;
		} else {
			pfrinfo->pskb = pskb;
			pfrinfo->pktlen = (cmd & RX_PktLenMask) - _CRCLNG_;

#if defined(RX_BUFFER_GATHER)
			if ((cmd & (RX_FirstSeg | RX_LastSeg)) != (RX_FirstSeg | RX_LastSeg)) {
				if ((cmd & RX_FirstSeg) && priv->pshare->gather_state == GATHER_STATE_NO) {
					priv->pshare->gather_state = GATHER_STATE_FIRST;
					priv->pshare->gather_len = pfrinfo->pktlen;
					pfrinfo->gather_flag = GATHER_FIRST;
				} else if (!(cmd & (RX_FirstSeg | RX_LastSeg)) &&
						(priv->pshare->gather_state == GATHER_STATE_FIRST || priv->pshare->gather_state == GATHER_STATE_MIDDLE)) {
					priv->pshare->gather_state = GATHER_STATE_MIDDLE;
					priv->pshare->gather_len += pfrinfo->pktlen;
					pfrinfo->gather_flag = GATHER_MIDDLE;
				} else if ((cmd & RX_LastSeg) &&
						(priv->pshare->gather_state == GATHER_STATE_FIRST || priv->pshare->gather_state == GATHER_STATE_MIDDLE)) {
					priv->pshare->gather_state = GATHER_STATE_LAST;
					pfrinfo->pktlen -= priv->pshare->gather_len;
					pfrinfo->gather_flag = GATHER_LAST;
				} else {
					if (priv->pshare->gather_state != GATHER_STATE_NO) {
						DEBUG_ERR("Rx pkt not in sequence [%x, %x]!\n", (cmd & (RX_FirstSeg | RX_LastSeg)), priv->pshare->gather_state);
						flush_rx_list(priv);
						priv->pshare->gather_state = GATHER_STATE_NO;
					}
					goto rx_reuse;
				}
			} else {
				if (priv->pshare->gather_state != GATHER_STATE_NO) {
					DEBUG_ERR("Rx pkt not in valid gather state [%x]!\n", priv->pshare->gather_state);
					flush_rx_list(priv);
					priv->pshare->gather_state = GATHER_STATE_NO;
				}
			}
			if (pfrinfo->gather_flag && pfrinfo->gather_flag != GATHER_FIRST) {
				pfrinfo->driver_info_size = 0;
				pfrinfo->rxbuf_shift = 0;
			}
			else
#endif /* RX_BUFFER_GATHER */
			{
				pfrinfo->driver_info_size = ((cmd >> RX_DrvInfoSizeSHIFT) & RX_DrvInfoSizeMask)<<3;
				pfrinfo->rxbuf_shift = (cmd & (RX_ShiftMask << RX_ShiftSHIFT)) >> RX_ShiftSHIFT;
			}

#if defined(RX_BUFFER_GATHER)
			if (pfrinfo->gather_flag) {
				pfrinfo->pktlen -= (pfrinfo->driver_info_size - 4);
				priv->pshare->gather_len -= (pfrinfo->driver_info_size - 4);
			}
#endif
			pfrinfo->sw_dec = (cmd & RX_SwDec) >> 27;
			pfrinfo->pktlen -= pfrinfo->rxbuf_shift;
			if ((pfrinfo->pktlen > 0x2000) || (pfrinfo->pktlen < 16)) {
#if defined(RX_BUFFER_GATHER)
				if (!(pfrinfo->gather_flag && (pfrinfo->pktlen < 16)))
#endif
				{
					printk("pfrinfo->pktlen=%d, goto rx_reuse\n",pfrinfo->pktlen);
					goto rx_reuse;
				}
			}

//#ifdef PCIE_POWER_SAVING_DEBUG
#if 0
			if(priv->firstPkt)	{
				unsigned char *pp= pdesc ;
				printk("rx isr, first pkt: len=%d\n[",  pfrinfo->pktlen);
			//	printHex(pp, sizeof(struct rx_frinfo));
			//	printk("------------------\n");
				printHex(pskb->data+32,pfrinfo->pktlen);
				priv->firstPkt=0;
				printk("]\n\n");
			}
#endif

#if defined(CONFIG_RTL865X_CMO)
			if (pfrinfo->pskb == NULL) {
				panic_printk("  rtl8192cd_rx_isr(): pfrinfo->pskb = NULL.\n");
				pfrinfo->pskb = pskb;
				goto rx_reuse;
			}
#endif

			pfrinfo->driver_info = (struct RxFWInfo *)(get_pframe(pfrinfo));
			pfrinfo->physt = (get_desc(pdesc->Dword0) & RX_PHYST)? 1:0;
			pfrinfo->faggr    = (get_desc(pdesc->Dword1) & RX_FAGGR)? 1:0;
			pfrinfo->paggr    = (get_desc(pdesc->Dword1) & RX_PAGGR)? 1:0;
			pfrinfo->rx_bw    = (get_desc(pdesc->Dword3) & RX_BW)? 1:0;
			pfrinfo->rx_splcp = (get_desc(pdesc->Dword3) & RX_SPLCP)? 1:0;

			if ((get_desc(pdesc->Dword3)&RX_RxMcsMask) < 12) {
				pfrinfo->rx_rate = dot11_rate_table[(get_desc(pdesc->Dword3)&RX_RxMcsMask)];
			} else {
				pfrinfo->rx_rate = 0x80|((get_desc(pdesc->Dword3)&RX_RxMcsMask)-12);
			}

			if (!pfrinfo->physt) {
				pfrinfo->rssi = 0;
			} else {
#if defined(RX_BUFFER_GATHER)
				if (pfrinfo->driver_info_size > 0)
#endif
				{
#ifdef USE_OUT_SRC				
					translate_rssi_sq(priv, pfrinfo, (get_desc(pdesc->Dword3)&RX_RxMcsMask));
#else
					translate_rssi_sq(priv, pfrinfo);
#endif
				}
			}

#if defined(CONFIG_RTL8190_PRIV_SKB)
			{
#if defined(DELAY_REFILL_RX_BUF)
				RTL_WLAN_RX_ATOMIC_PROTECT_ENTER;
				cmp_flags = (CIRC_CNT_RTK(tail, phw->cur_rx_refill, NUM_RX_DESC) > REFILL_THRESHOLD);
				RTL_WLAN_RX_ATOMIC_PROTECT_EXIT;
				if (cmp_flags) {
					DEBUG_WARN("out of skb_buff\n");
					priv->ext_stats.reused_skb++;
					goto rx_reuse;
				}
				new_skb = NULL;
#else
//				if (skb_free_num== 0 && priv->pshare->skb_queue.qlen == 0) {
				new_skb = rtl_dev_alloc_skb(priv, RX_BUF_LEN, _SKB_RX_, 0);
				if (new_skb == NULL) {
					DEBUG_WARN("out of skb_buff\n");
					priv->ext_stats.reused_skb++;
					goto rx_reuse;
				}
#endif
			}
#else	/* defined(CONFIG_RTL8190_PRIV_SKB) */
			// allocate new one in advance
			new_skb = rtl_dev_alloc_skb(priv, RX_BUF_LEN, _SKB_RX_, 0);
			if (new_skb == NULL) {
				DEBUG_WARN("out of skb_buff\n");
				priv->ext_stats.reused_skb++;
				goto rx_reuse;
			}
#endif

			/*-----------------------------------------------------
			 validate_mpdu will check if we still can reuse the skb
			------------------------------------------------------*/
#if defined(CONFIG_RTL_QOS_PATCH) || defined(CONFIG_RTK_VOIP_QOS) || defined(CONFIG_RTK_VLAN_WAN_TAG_SUPPORT)
			pskb->srcPhyPort = QOS_PATCH_RX_FROM_WIRELESS;
#endif

#if defined(MP_TEST)
			if (OPMODE & WIFI_MP_STATE) {
				skb_reserve(pskb, (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size));
				reuse = 1;
				find_flag=1;
				//-------------------------------------------------------------------------------------------
				if((OPMODE & WIFI_MP_ARX_FILTER ) && (OPMODE & WIFI_MP_RX ) )
				{
					pframe = get_pframe(pfrinfo);
					sa = 	get_sa(pframe);
					da = 	get_da(pframe);
					bssid =get_bssid_mp(pframe);
					if(priv->pshare->mp_filter_flag & 0x1)
					{
						//sa = 	get_sa(pframe);
						if(memcmp(priv->pshare->mp_filter_SA,sa,MACADDRLEN))
						{
							find_flag=0;
						}
					}
					if(find_flag)
					{
						if(priv->pshare->mp_filter_flag & 0x2)
						{
							//da = 	get_da(pframe);
							if(memcmp(priv->pshare->mp_filter_DA,da,MACADDRLEN))
							{
								find_flag=0;
							}
						}
					}
					if(find_flag)
					{
						if(priv->pshare->mp_filter_flag & 0x4)
						{
							//bssid =get_bssid_mp(pframe);
							if(memcmp(priv->pshare->mp_filter_BSSID,bssid,MACADDRLEN))
							{
								find_flag=0;
							}
						}
					}
#if 0
					if(find_flag)
					{
						printk("flag: %x\nSA: %02x:%02x:%02x:%02x:%02x:%02x\nDA: %02x:%02x:%02x:%02x:%02x:%02x\nBSSID: %02x:%02x:%02x:%02x:%02x:%02x\n",priv->pshare->mp_filter_flag,
															sa[0],
															sa[1],
															sa[2],
															sa[3],
															sa[4],
															sa[5],
															da[0],
															da[1],
															da[2],
															da[3],
															da[4],
															da[5],
															bssid[0],
															bssid[1],
															bssid[2],
															bssid[3],
															bssid[4],
															bssid[5]);
					}
#endif
				}
	//-------------------------------------------------------------------------------------------
				if(find_flag)
				{
#if defined(B2B_TEST)
					mp_validate_rx_packet(priv, pskb->data, pfrinfo->pktlen);
#endif
					rx_sum_up(priv, NULL, pfrinfo->pktlen, GetRetry(get_pframe(pfrinfo)));
					if (priv->pshare->rf_ft_var.rssi_dump)
						update_sta_rssi(priv, NULL, pfrinfo);
				}
					pskb->data -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
					pskb->tail -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
			}
			else
#endif // defined(MP_TEST)
			{
#if defined(RX_BUFFER_GATHER)
				#define	RTL_WLAN_DRV_RX_GATHER_GAP_THRESHOLD	32
#else
				#define	RTL_WLAN_DRV_RX_GATHER_GAP_THRESHOLD	64
#endif

				if (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size + pfrinfo->pktlen + _CRCLNG_ <= (RX_BUF_LEN- sizeof(struct rx_frinfo)-RTL_WLAN_DRV_RX_GATHER_GAP_THRESHOLD) ) {
					skb_reserve(pskb, (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size));

					if (cmd & RX_ICVERR) {
						rtl8192cd_ICV = privacy = 0;
						pstat = NULL;

#if defined(WDS) || defined(CONFIG_RTK_MESH) || defined(A4_STA)
						if (get_tofr_ds((unsigned char *)get_pframe(pfrinfo)) == 3) {
							pstat = get_stainfo(priv, (unsigned char *)GetAddr2Ptr((unsigned char *)get_pframe(pfrinfo)));
						} else
#endif
							{pstat = get_stainfo(priv, (unsigned char *)get_sa((unsigned char *)get_pframe(pfrinfo)));}

						if (!pstat) {
							rtl8192cd_ICV++;
						} else {
							if (OPMODE & WIFI_AP_STATE) {
#if defined(WDS) || defined(CONFIG_RTK_MESH)
								if (get_tofr_ds((unsigned char *)get_pframe(pfrinfo)) == 3){
#if defined(CONFIG_RTK_MESH)
									if(priv->pmib->dot1180211sInfo.mesh_enable) {
										privacy = (IS_MCAST(GetAddr1Ptr((unsigned char *)get_pframe(pfrinfo)))) ? _NO_PRIVACY_ : priv->pmib->dot11sKeysTable.dot11Privacy;
									} else
#endif
										{privacy = priv->pmib->dot11WdsInfo.wdsPrivacy;}
								}
								else
#endif	/*	defined(WDS) || defined(CONFIG_RTK_MESH)	*/
									{privacy = get_sta_encrypt_algthm(priv, pstat);}
							}
#if defined(CLIENT_MODE)
							else {
									privacy = get_sta_encrypt_algthm(priv, pstat);
							}
#endif

							if (privacy != _CCMP_PRIVACY_)
								rtl8192cd_ICV++;
						}

						if (rtl8192cd_ICV) {
							rx_pkt_exception(priv, cmd);
							pskb->data -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
							pskb->tail -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
#if !defined(DELAY_REFILL_RX_BUF) || !defined(CONFIG_RTL8190_PRIV_SKB) //we create this, but we do not free it!
							if (new_skb != NULL)
								rtl_kfree_skb(priv, new_skb, _SKB_RX_);
#endif
							goto rx_reuse;
						}

					}

//					printk("pkt in\n");
					SNMP_MIB_INC(dot11ReceivedFragmentCount, 1);

#if defined(SW_ANT_SWITCH)
					if(priv->pshare->rf_ft_var.antSw_enable) {
						dm_SWAW_RSSI_Check(priv, pfrinfo);
					}
#endif

#if defined(RX_BUFFER_GATHER)
					if (priv->pshare->gather_state != GATHER_STATE_NO) {
						list_add_tail(&pfrinfo->rx_list, &priv->pshare->gather_list);

						if (priv->pshare->gather_state == GATHER_STATE_LAST) {
							if (!search_first_segment(priv, &pfrinfo))
								reuse = 0;
							else {
								pskb = pfrinfo->pskb;
								pfrinfo_update = 1;
							}
							priv->pshare->gather_state = GATHER_STATE_NO;
						} else {
							reuse = 0;
						}
					}
					if (priv->pshare->gather_state == GATHER_STATE_NO && reuse)
#endif
					{
						reuse = validate_mpdu(priv, pfrinfo);
					}

					if (reuse) {
						pskb->data -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
						pskb->tail -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);

#ifdef RX_BUFFER_GATHER
                        if (pfrinfo->gather_flag & GATHER_FIRST){
                            //flush_rx_list(priv);
                            rtl_kfree_skb(priv, pskb, _SKB_RX_);
                            reuse = 0;
                            DEBUG_WARN("Gather-First packet error, free skb\n");
                        }
#endif
					}
				}
				#undef	RTL_WLAN_DRV_RX_GATHER_GAP_THRESHOLD
			}
		}	/* if (cmd&XXXX) */

		if (!reuse) {
			phw->rx_infoL[tail].pbuf = NULL;        // clear pointer for not being accidently freed
#if defined(CONFIG_NET_PCI) && !defined(USE_RTL8186_SDK)
			if (IS_PCIBIOS_TYPE) {
				pci_unmap_single(priv->pshare->pdev, phw->rx_infoL[tail].paddr, (RX_BUF_LEN - sizeof(struct rx_frinfo)), PCI_DMA_FROMDEVICE);
			}
#endif

#if 0
			// allocate new one
			pskb = rtl_dev_alloc_skb(priv, RX_BUF_LEN, _SKB_RX_);

			if (pskb == (struct sk_buff *)NULL)
			{
				DEBUG_WARN("out of skb_buff\n");
				list_del(&pfrinfo->rx_list);
				pskb = (struct sk_buff *)(phw->rx_infoL[tail].pbuf);
				pskb->data -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
				pskb->tail -= (pfrinfo->rxbuf_shift + pfrinfo->driver_info_size);
				goto rx_reuse;
			}
#endif

#if defined(CONFIG_RTL8190_PRIV_SKB) && defined(DELAY_REFILL_RX_BUF)
#ifdef CONCURRENT_MODE
			if (skb_free_num[priv->pshare->wlandev_idx] == 0 && priv->pshare->skb_queue.qlen == 0)
#else
			if (skb_free_num == 0 && priv->pshare->skb_queue.qlen == 0)
#endif
			{
				refill = 0;
				goto rx_done;
			}

			new_skb = rtl_dev_alloc_skb(priv, RX_BUF_LEN, _SKB_RX_, 0);
			ASSERT(new_skb);
			if(new_skb == NULL) {
				printk("not enough memory...\n");
				refill = 0;
				goto rx_done;
			}
#endif
			pskb = new_skb;


#if defined(DELAY_REFILL_RX_BUF)
			RTL_WLAN_RX_ATOMIC_PROTECT_ENTER;
			init_rxdesc(pskb, phw->cur_rx_refill, priv);
			RTL_WLAN_RX_ATOMIC_PROTECT_EXIT;
#else
			init_rxdesc(pskb, tail, priv);
#endif

			goto rx_done;
		}	/* if (!reuse) */
#if !defined(DELAY_REFILL_RX_BUF)
		else {
			rtl_kfree_skb(priv, new_skb, _SKB_RX_);
		}
#endif

rx_reuse:
#if defined(DELAY_REFILL_RX_BUF)
		RTL_WLAN_RX_ATOMIC_PROTECT_ENTER;
#if defined(RX_BUFFER_GATHER)
		cmp_flags = (tail != phw->cur_rx_refill) || (pfrinfo_update);
#else
		cmp_flags = (tail != phw->cur_rx_refill);
#endif
		RTL_WLAN_RX_ATOMIC_PROTECT_EXIT;

		if (cmp_flags)
		{
			phw->rx_infoL[tail].pbuf = NULL;        // clear pointer for not being accidently freed
			pskb->data = pskb->head;
			pskb->tail = pskb->head;
			skb_reserve(pskb, 128);
#if defined(CONFIG_RTL8196_RTL8366)
			skb_reserve(pskb, 8);
#endif
#if defined(CONFIG_RTK_VOIP_VLAN_ID)
			skb_reserve(pskb, 4);
#endif
			refill_rx_ring(priv, pskb, NULL);
			refill = 0;
		} else
#endif	/*	defined(DELAY_REFILL_RX_BUF)	*/
		{
#if defined(RX_BUFFER_GATHER)
			pfrinfo->gather_flag = 0;
#endif
			SMP_LOCK_SKB(x);
			pdesc->Dword6 = set_desc(phw->rx_infoL[tail].paddr);
			rtl_cache_sync_wback(priv, phw->rx_infoL[tail].paddr, RX_BUF_LEN - sizeof(struct rx_frinfo)-64, PCI_DMA_FROMDEVICE);
			pdesc->Dword0 = set_desc((tail == (NUM_RX_DESC - 1)? RX_EOR : 0) | RX_OWN | (RX_BUF_LEN - sizeof(struct rx_frinfo)-64));
			SMP_UNLOCK_SKB(x);
		}

rx_done:
		RTL_WLAN_RX_ATOMIC_PROTECT_ENTER;
		//tail = (tail + 1) % NUM_RX_DESC;
		tail = ( ((tail+1)==NUM_RX_DESC)?0:tail+1);
		phw->cur_rx = tail;

#if defined(DELAY_REFILL_RX_BUF)
		if (refill) {
			//phw->cur_rx_refill = (phw->cur_rx_refill + 1) % NUM_RX_DESC;
			phw->cur_rx_refill = ( ((phw->cur_rx_refill+1)==NUM_RX_DESC)?0:phw->cur_rx_refill+1);
		}
#endif
		RTL_WLAN_RX_ATOMIC_PROTECT_EXIT;
	}	/*	while(1)	*/

	if (!IS_DRV_OPEN(priv))
		return;

#if defined(RTL8190_ISR_RX) && defined(RTL8190_DIRECT_RX)
	if (OPMODE & WIFI_AP_STATE) {
		if (!list_empty(&priv->wakeup_list))
			process_dzqueue(priv);

#if defined(MBSSID)
		if (priv->pmib->miscEntry.vap_enable) {
			int i;
			struct rtl8192cd_priv *priv_vap;

			for (i=0; i<RTL8192CD_NUM_VWLAN; i++) {
				if (IS_DRV_OPEN(priv->pvap_priv[i])) {
					priv_vap = priv->pvap_priv[i];
					if (!list_empty(&priv_vap->wakeup_list))
						process_dzqueue(priv_vap);
				}
			}
		}
#endif
	}
#ifdef UNIVERSAL_REPEATER
	else {
		if (IS_DRV_OPEN(GET_VXD_PRIV(priv)))
			if (!list_empty(&GET_VXD_PRIV(priv)->wakeup_list))
				process_dzqueue(GET_VXD_PRIV(priv));
	}
#endif
#endif	/*	defined(RTL8190_ISR_RX) && defined(RTL8190_DIRECT_RX)	*/

	if(priv->pshare->skip_mic_chk)
		--priv->pshare->skip_mic_chk;
}

#undef	RTL_WLAN_RX_ATOMIC_PROTECT_ENTER
#undef	RTL_WLAN_RX_ATOMIC_PROTECT_EXIT

// The purpose of reassemble is to assemble the frag into a complete one.
static struct rx_frinfo *reassemble(struct rtl8192cd_priv *priv, struct stat_info *pstat)
{
	struct list_head	*phead, *plist;
	unsigned short		seq=0;
	unsigned char		tofr_ds=0;
	unsigned int		iv, icv, mic, privacy=0, offset;
	struct sk_buff		*pskb;
	struct rx_frinfo	*pfrinfo, *pfirstfrinfo=NULL;
	unsigned char		*pframe=NULL, *pfirstframe=NULL, tail[16]; //4, 12, and 8 for WEP/TKIP/AES
	unsigned long		flags;
	int			i;

	phead = &pstat->frag_list;

	SAVE_INT_AND_CLI(flags);

	// checking  all the seq should be the same, and the frg should monotically increase
	for(i=0, plist=phead->next; plist!=phead; plist=plist->next, i++)
	{
		pfrinfo = list_entry(plist, struct rx_frinfo, mpdu_list);
		pframe = get_pframe(pfrinfo);

		if ((GetFragNum(pframe)) != i)
		{
			DEBUG_ERR("RX DROP: FragNum did not match, FragNum=%d, GetFragNum(pframe)=%d\n",
				i, GetFragNum(pframe));
			goto unchainned_all;
		}

		if (i == 0)
		{
			seq = GetSequence(pframe);
			privacy = GetPrivacy(pframe);
			tofr_ds = pfrinfo->to_fr_ds;
		}
		else
		{
			if (GetSequence(pframe) != seq)
			{
				DEBUG_ERR("RX DROP: Seq is not correct, seq=%d, GetSequence(pframe)=%d\n",
					seq, GetSequence(pframe));
				goto unchainned_all;
			}

			if (GetPrivacy(pframe) != privacy)
			{
				DEBUG_ERR("RX DROP: Privacy is not correct, privacy=%d, GetPrivacy(pframe)=%d\n",
					privacy, GetPrivacy(pframe));
				goto unchainned_all;
			}

			if (pfrinfo->to_fr_ds != tofr_ds)
			{
				DEBUG_ERR("RX DROP: to_fr_ds did not match, tofr_ds=%d, pfrinfo->to_fr_ds=%d\n",
					tofr_ds, pfrinfo->to_fr_ds);
				goto unchainned_all;
			}
		}
	}

	privacy = get_privacy(priv, pstat, &iv, &icv, &mic);

	offset = iv;
	offset += get_hdrlen(priv, pframe);

	// below we are going to re-assemble the whole pkts...
	for(i=0, plist=phead->next; plist!=phead; plist=plist->next, i++)
	{
		pfrinfo = list_entry(plist, struct rx_frinfo, mpdu_list);

		if (pfrinfo->pktlen <= (offset + icv + mic))
		{
			DEBUG_ERR("RX DROP: Frame length bad (%d)\n", pfrinfo->pktlen);
			pfirstfrinfo = NULL;
			goto unchainned_all;
		}

		if (i == 0)
		{
			pfirstfrinfo = pfrinfo;
			pfirstframe  = get_pframe(pfrinfo);
			pfirstframe += pfrinfo->pktlen - (icv + mic);

			if (icv + mic)
			{
				memcpy((void *)tail, (void *)(pfirstframe), (icv + mic));
				pfirstfrinfo->pktlen -= (icv + mic);
			}
			continue;
		}

		// check if too many frags...
		if ((pfirstfrinfo->pktlen += (pfrinfo->pktlen - offset - icv - mic)) >=
			(RX_BUF_LEN - offset - icv - mic - 200))
		{
			DEBUG_ERR("RX DROP: over rx buf size after reassemble...\n");
			pfirstfrinfo = NULL;
			goto unchainned_all;
		}

		// here we should check if all these frags exceeds the buf size
		memcpy(pfirstframe, get_pframe(pfrinfo) + offset, pfrinfo->pktlen - offset - icv - mic);
		pfirstframe += (pfrinfo->pktlen - offset - icv - mic);
	}

	if (icv + mic)
	{
		memcpy((void *)pfirstframe, (void *)tail, (icv + mic));
		pfirstfrinfo->pktlen += (icv + mic);
	}

	// take the first frame out of fragment list
	plist = phead->next;
	list_del_init(plist);

unchainned_all:	// dequeue all the queued-up frag, free skb, and init_list_head again...

	while(!list_empty(phead))
	{
		plist = phead->next;
		pfrinfo = list_entry(plist, struct rx_frinfo, mpdu_list);
		pskb = get_pskb(pfrinfo);
		list_del_init(plist);
		if (pfirstfrinfo == NULL)
			priv->ext_stats.rx_data_drops++;
		rtl_kfree_skb(priv, pskb, _SKB_RX_IRQ_);
	}
	INIT_LIST_HEAD(phead);

	list_del_init(&pstat->defrag_list);
	pstat->frag_count = 0;

	RESTORE_INT(flags);

	return pfirstfrinfo;
}


/*----------------------------------------------------------------------------------------
	So far, only data pkt will be defragmented.
-----------------------------------------------------------------------------------------*/
static struct rx_frinfo *defrag_frame_main(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	unsigned char *da, *sa;
	struct stat_info *pstat=NULL;
	unsigned int  res, hdr_len, len;
	int status, privacy=0, pos;
	unsigned long flags;
	unsigned char tkipmic[8], rxmic[8];
	unsigned char *pframe;

	pframe = get_pframe(pfrinfo);
	hdr_len = pfrinfo->hdr_len;
	da	= pfrinfo->da;
	sa  = pfrinfo->sa;
	len = pfrinfo->pktlen;

	/*---------first of all check if sa is assocated---------*/
	if (OPMODE & WIFI_AP_STATE) {
#if defined(CONFIG_RTK_MESH) || defined(WDS)
		// for 802.11s case, pstat will not be NULL, because we have check it in validate-mpdu
		if (pfrinfo->to_fr_ds == 3) {
			pstat = get_stainfo(priv, GetAddr2Ptr(pframe));
			if (pstat == NULL){
			    priv->ext_stats.rx_data_drops++;
			    DEBUG_ERR("RX Drop: WDS rx data with pstat == NULL\n");
			    goto free_skb_in_defrag;
				}
				else {
			    goto check_privacy;
				}
			}
		else
#endif
		{
#ifdef A4_STA
			if (pfrinfo->to_fr_ds == 3 &&  priv->pshare->rf_ft_var.a4_enable) {
				pstat = get_stainfo(priv, GetAddr2Ptr(pframe));
				if (pstat && !(pstat->state & WIFI_A4_STA))
					add_a4_client(priv, pstat);

				a4_sta_add(priv, pstat, sa);
			}
			else
#endif
		                pstat = get_stainfo(priv, sa);
                }
	}
#ifdef CLIENT_MODE
	else if (OPMODE & WIFI_STATION_STATE) {
		unsigned char *bssid = GetAddr2Ptr(pframe);
		pstat = get_stainfo(priv, bssid);
	}
	else	// Ad-hoc
		pstat = get_stainfo(priv, sa);
#endif

	if (pstat == NULL)
	{
		status = _RSON_CLS2_;
		priv->ext_stats.rx_data_drops++;
		DEBUG_ERR("RX DROP: class 2 error!\n");
		goto data_defrag_error;
	}
	else if (!(pstat->state & WIFI_ASOC_STATE))
	{
		status = _RSON_CLS3_;
		priv->ext_stats.rx_data_drops++;
		DEBUG_ERR("RX DROP: class 3 error!\n");
		goto data_defrag_error;
	}
	else {}

	/*-------------------get privacy-------------------*/
#if defined(CONFIG_RTK_MESH) || defined(WDS)
check_privacy:
#endif

	if (OPMODE & WIFI_AP_STATE) {
#ifdef CONFIG_RTK_MESH
		//modify by Joule for SECURITY
		if(pfrinfo->is_11s)
			privacy = IS_MCAST(da) ? _NO_PRIVACY_ : get_sta_encrypt_algthm(priv, pstat);
		else
#endif
#ifdef WDS
		if (pfrinfo->to_fr_ds == 3)
			privacy = priv->pmib->dot11WdsInfo.wdsPrivacy;
		else
#endif
		privacy = get_sta_encrypt_algthm(priv, pstat);
	}
#ifdef CLIENT_MODE
	else {
		if (IS_MCAST(da)) {
#if defined(UNIVERSAL_REPEATER) && defined(MBSSID)
			if (IS_VXD_INTERFACE(priv) && !IEEE8021X_FUN &&
				((priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm == _WEP_40_PRIVACY_) ||
				 (priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm == _WEP_104_PRIVACY_)))
				 privacy = priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm;
			else
#endif
#if defined(CONFIG_RTL_WAPI_SUPPORT)
			if (pstat&&pstat->wapiInfo&&pstat->wapiInfo->wapiType!=wapiDisable)
				privacy = _WAPI_SMS4_;
			else
#endif
			// iv, icv and mic are not be used below. Don't care them!
				privacy = priv->pmib->dot11GroupKeysTable.dot11Privacy;
		}
		else
		{
			privacy = get_sta_encrypt_algthm(priv, pstat);
		}
	}

	if (IS_MCAST(da))
    {
        if (GetTupleCache(pframe) == pstat->tpcache_mcast)
        {
            priv->ext_stats.rx_decache++;
            SNMP_MIB_INC(dot11FrameDuplicateCount, 1);
            goto free_skb_in_defrag;
        }
        else
            pstat->tpcache_mcast = GetTupleCache(pframe);
    }
	else
#endif
		/*-------------------check retry-------------------*/
	if (is_qos_data(pframe)) {
		pos = GetSequence(pframe) & (TUPLE_WINDOW - 1);
		if (GetTupleCache(pframe) == pstat->tpcache[pfrinfo->tid][pos]) {
			priv->ext_stats.rx_decache++;
			SNMP_MIB_INC(dot11FrameDuplicateCount, 1);
			goto free_skb_in_defrag;
		}
		else
			pstat->tpcache[pfrinfo->tid][pos] = GetTupleCache(pframe);
	}
	else {
		if (GetRetry(pframe)) {
			if (GetTupleCache(pframe) == pstat->tpcache_mgt) {
				priv->ext_stats.rx_decache++;
				SNMP_MIB_INC(dot11FrameDuplicateCount, 1);
				goto free_skb_in_defrag;
			}
		}
		pstat->tpcache_mgt = GetTupleCache(pframe);
	}

	/*-------------------------------------------------------*/
	/*-----------insert MPDU-based decrypt below-------------*/
	/*-------------------------------------------------------*/

#ifdef SUPPORT_SNMP_MIB
	if (GetPrivacy(pframe) && privacy == _NO_PRIVACY_)
		SNMP_MIB_INC(dot11WEPUndecryptableCount, 1);

	if (!GetPrivacy(pframe) && privacy != _NO_PRIVACY_)
		SNMP_MIB_INC(dot11WEPExcludedCount, 1);
#endif

	// check whether WEP bit is set in mac header and sw encryption
	if (GetPrivacy(pframe) && UseSwCrypto(priv, pstat, IS_MCAST(GetAddr1Ptr(pframe))))
	{
#if defined(CONFIG_RTL_WAPI_SUPPORT)
		if (privacy==_WAPI_SMS4_)
		{
			/*	Decryption	*/
//			SAVE_INT_AND_CLI(flags);
			res = SecSWSMS4Decryption(priv, pstat, pfrinfo);
//			RESTORE_INT(flags);
			if (res == FAIL)
			{
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: WAPI decrpt error!\n");
				goto free_skb_in_defrag;
			}
			pframe = get_pframe(pfrinfo);
		} else
#endif
		if (privacy == _TKIP_PRIVACY_)
		{
			res = tkip_decrypt(priv, pfrinfo, pfrinfo->pktlen);
			if (res == FAIL)
			{
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: Tkip decrpt error!\n");
				goto free_skb_in_defrag;
			}
		}
		else if (privacy == _CCMP_PRIVACY_)
		{
			res = aesccmp_decrypt(priv, pfrinfo);
			if (res == FAIL)
			{
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: AES decrpt error!\n");
				goto free_skb_in_defrag;
			}
		}
		else if (privacy == _WEP_40_PRIVACY_ || privacy == _WEP_104_PRIVACY_)
		{
			res = wep_decrypt(priv, pfrinfo, pfrinfo->pktlen, privacy, 0);
			if (res == FAIL)
			{
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: WEP decrpt error!\n");
				goto free_skb_in_defrag;
			}
		}
		else
		{
			DEBUG_ERR("RX DROP: encrypted packet but no key in sta or wrong enc type!\n");
			goto free_skb_in_defrag;
		}

	}
	/*----------------End of MPDU-based decrypt--------------*/

	if (GetMFrag(pframe))
	{
		if (pstat->frag_count > MAX_FRAG_COUNT)
		{
			priv->ext_stats.rx_data_drops++;
			DEBUG_ERR("RX DROP: station has received too many frags!\n");
			goto free_skb_in_defrag;
		}

		SAVE_INT_AND_CLI(flags);

		if (pstat->frag_count == 0) // the first frag...
			pstat->frag_to = priv->frag_to;

		if (list_empty(&pstat->defrag_list))
			list_add_tail(&pstat->defrag_list, &priv->defrag_list);

		list_add_tail(&pfrinfo->mpdu_list, &pstat->frag_list);
		pstat->frag_count++;

		RESTORE_INT(flags);
		return (struct rx_frinfo *)NULL;
	}
	else
	{
		if(GetFragNum(pframe))
		{
			SAVE_INT_AND_CLI(flags);
			list_add_tail(&pfrinfo->mpdu_list, &pstat->frag_list);
			RESTORE_INT(flags);

			pfrinfo = reassemble(priv, pstat);
			if (pfrinfo == NULL)
				return (struct rx_frinfo *)NULL;
		}
	}

	/*-----discard non-authorized packet before MIC check----*/
	if (OPMODE & WIFI_AP_STATE) {
#if defined(CONFIG_RTK_MESH) || defined(WDS)
		if (pfrinfo->to_fr_ds != 3)
#endif
		if (auth_filter(priv, pstat, pfrinfo) == FAIL) {
			priv->ext_stats.rx_data_drops++;
			DEBUG_ERR("RX DROP: due to auth_filter fails\n");
			goto free_skb_in_defrag;
		}
	}
#ifdef CLIENT_MODE
	else if (OPMODE & WIFI_STATION_STATE) {
		if (auth_filter(priv, pstat, pfrinfo) == FAIL) {
			priv->ext_stats.rx_data_drops++;
			DEBUG_ERR("RX DROP: due to auth_filter fails\n");
			goto free_skb_in_defrag;
		}
	}
#endif

	/*------------------------------------------------------------------------*/
						//insert MSDU-based digest here!
	/*------------------------------------------------------------------------*/
	if (privacy == _TKIP_PRIVACY_)
	{
		pframe = get_pframe(pfrinfo);
		len = pfrinfo->pktlen;

		//truncate Michael...
		memcpy((void *)rxmic, (void *)(pframe + len - 8 - 4), 8); // 8 michael, 4 icv
		SAVE_INT_AND_CLI(flags);
		tkip_rx_mic(priv, pframe, da, sa,
			pfrinfo->tid, pframe + hdr_len + 8,
			len - hdr_len - 8 - 8 - 4, tkipmic, 0); // 8 IV, 8 Mic, 4 ICV
		RESTORE_INT(flags);

		if(memcmp(rxmic, tkipmic, 8))
		{
			priv->ext_stats.rx_data_drops++;
#ifdef _SINUX_
			printk("RX DROP: MIC error! Indicate to protection mechanism\n");
			mic_error_report(0);
#else
			DEBUG_ERR("RX DROP: MIC error! Indicate to protection mechanism\n");
#endif
			if (OPMODE & WIFI_AP_STATE) {
#ifdef RTL_WPA2
#ifdef _SINUX_
				printk("%s: DOT11_Indicate_MIC_Failure %02X:%02X:%02X:%02X:%02X:%02X \n", (char *)__FUNCTION__,pstat->hwaddr[0],pstat->hwaddr[1],pstat->hwaddr[2],pstat->hwaddr[3],pstat->hwaddr[4],pstat->hwaddr[5]);
#else
				PRINT_INFO("%s: DOT11_Indicate_MIC_Failure %02X:%02X:%02X:%02X:%02X:%02X \n", (char *)__FUNCTION__,pstat->hwaddr[0],pstat->hwaddr[1],pstat->hwaddr[2],pstat->hwaddr[3],pstat->hwaddr[4],pstat->hwaddr[5]);
#endif

#endif
#ifdef WDS
				if ((pfrinfo->to_fr_ds == 3) &&
						pstat && (pstat->state & WIFI_WDS))
					goto free_skb_in_defrag;
#endif
				DOT11_Indicate_MIC_Failure(priv->dev, pstat);
			}
#ifdef CLIENT_MODE
			else if (OPMODE & WIFI_STATION_STATE)
				DOT11_Indicate_MIC_Failure_Clnt(priv, sa);
#endif
			goto free_skb_in_defrag;
		}
	}

	return pfrinfo;

data_defrag_error:

	if (OPMODE & WIFI_AP_STATE)
		issue_deauth(priv,sa,status);
#ifdef CLIENT_MODE
	else {
		if (pstat == NULL) {
			DEBUG_ERR("rx data with pstat == NULL\n");
		}
		else if (!(pstat->state & WIFI_ASOC_STATE)) {
			DEBUG_ERR("rx data with pstat not associated\n");
		}
	}
#endif

free_skb_in_defrag:

	rtl_kfree_skb(priv, get_pskb(pfrinfo), _SKB_RX_);

	return (struct rx_frinfo *)NULL;
}


static struct rx_frinfo *defrag_frame(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	struct sk_buff *pskb = get_pskb(pfrinfo);
	struct rx_frinfo *prx_frinfo = NULL;
	unsigned int encrypt;
	unsigned char *pframe;

	// rx a encrypt packet but encryption is not enabled in local mib, discard it
	pframe = get_pframe(pfrinfo);
	encrypt = GetPrivacy(pframe);

//modify by Joule for SECURITY
// here maybe need do some tune; plus
#ifdef CONFIG_RTK_MESH	/*-------*/
	if (encrypt && (
		(pfrinfo->to_fr_ds==3 && (
#ifdef WDS
		GET_MIB(priv)->dot1180211sInfo.mesh_enable ==0	?
		priv->pmib->dot11WdsInfo.wdsPrivacy==_NO_PRIVACY_ :
#endif
		priv->pmib->dot11sKeysTable.dot11Privacy ==_NO_PRIVACY_	)) ||
		(pfrinfo->to_fr_ds!=3 && priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm==_NO_PRIVACY_
#if defined(CONFIG_RTL_WAPI_SUPPORT)
			&& priv->pmib->wapiInfo.wapiType==wapiDisable
#endif
		)))
#else	/*-------*/
// origin
#ifdef WDS
	if (encrypt && (
			(pfrinfo->to_fr_ds==3 && priv->pmib->dot11WdsInfo.wdsPrivacy==_NO_PRIVACY_) ||
			(pfrinfo->to_fr_ds!=3 && priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm==_NO_PRIVACY_))
#if defined(CONFIG_RTL_WAPI_SUPPORT)
			&& priv->pmib->wapiInfo.wapiType==wapiDisable
#endif
	)
#else
	if (encrypt && priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm == _NO_PRIVACY_
#if defined(CONFIG_RTL_WAPI_SUPPORT)
			&& priv->pmib->wapiInfo.wapiType==wapiDisable
#endif
	)
#endif
#endif/*-------*/
	{
		priv->ext_stats.rx_data_drops++;
		DEBUG_ERR("RX DROP: Discard a encrypted packet!\n");
		rtl_kfree_skb(priv, pskb, _SKB_RX_);
		return (struct rx_frinfo *)NULL;
	}

#ifdef CONFIG_RTK_MESH
        if (pfrinfo->to_fr_ds==3 && !encrypt && (
#ifdef WDS
		GET_MIB(priv)->dot1180211sInfo.mesh_enable ==0	?
		priv->pmib->dot11WdsInfo.wdsPrivacy!=_NO_PRIVACY_ :
#endif
		(priv->pmib->dot11sKeysTable.dot11Privacy != _NO_PRIVACY_ && !IS_MCAST(pfrinfo->da)))) {
		priv->ext_stats.rx_data_drops++;
		DEBUG_ERR("RX DROP: Discard a un-encrypted WDS/MESH packet!\n");
		rtl_kfree_skb(priv, pskb, _SKB_RX_);
		SNMP_MIB_INC(dot11WEPExcludedCount, 1);
		return (struct rx_frinfo *)NULL;
	}
#else
//origin
#ifdef WDS
	if (pfrinfo->to_fr_ds==3 && !encrypt && priv->pmib->dot11WdsInfo.wdsPrivacy!=_NO_PRIVACY_) {
		priv->ext_stats.rx_data_drops++;
		DEBUG_ERR("RX DROP: Discard a un-encrypted WDS packet!\n");
		rtl_kfree_skb(priv, pskb, _SKB_RX_);
		SNMP_MIB_INC(dot11WEPExcludedCount, 1);
		return (struct rx_frinfo *)NULL;
	}
#endif
#endif
	prx_frinfo = defrag_frame_main(priv, pfrinfo);

	return prx_frinfo;
}


static int auth_filter(struct rtl8192cd_priv *priv, struct stat_info *pstat,
				struct rx_frinfo *pfrinfo)
{
	unsigned int	hdr_len;
	unsigned char	*pframe, *pbuf;
	unsigned short	proto;

	hdr_len = pfrinfo->hdr_len;
	pframe = get_pframe(pfrinfo);
	pbuf = pframe + hdr_len + sizeof(struct wlan_llc_t) + 3;
	proto = *(unsigned short *)pbuf;

	if(IEEE8021X_FUN) {
		if (pstat) {
			if (pstat->ieee8021x_ctrlport) // controlled port is enable...
				return SUCCESS;
			else {
				//only 802.1x frame can pass...
				if (proto == __constant_htons(0x888e))
					return SUCCESS;
				else {
					return FAIL;
				}
			}
		}
		else {
			DEBUG_ERR("pstat == NULL in auth_filter\n");
			return FAIL;
		}
	}

	return SUCCESS;
}


#if defined(WIFI_WMM) && defined(WMM_APSD)
void SendQosNullData(struct rtl8192cd_priv *priv, unsigned char *da)
{
	struct wifi_mib *pmib;
	unsigned char *hwaddr;
	unsigned char tempQosControl[2];
	DECLARE_TXINSN(txinsn);

	txinsn.retry = priv->pmib->dot11OperationEntry.dot11ShortRetryLimit;

	pmib = GET_MIB(priv);

	hwaddr = pmib->dot11OperationEntry.hwaddr;

	txinsn.q_num = MANAGE_QUE_NUM;
	txinsn.tx_rate = find_rate(priv, NULL, 0, 1);
	txinsn.lowest_tx_rate = txinsn.tx_rate;
	txinsn.fixed_rate = 1;
	txinsn.phdr = get_wlanhdr_from_poll(priv);
	txinsn.pframe = NULL;

	if (txinsn.phdr == NULL)
		goto send_qos_null_fail;

	memset((void *)(txinsn.phdr), 0, sizeof (struct	wlan_hdr));

	SetFrameSubType(txinsn.phdr, BIT(7) | WIFI_DATA_NULL);
	SetFrDs(txinsn.phdr);
	memcpy((void *)GetAddr1Ptr((txinsn.phdr)), da, MACADDRLEN);
	memcpy((void *)GetAddr2Ptr((txinsn.phdr)), hwaddr, MACADDRLEN);
	memcpy((void *)GetAddr3Ptr((txinsn.phdr)), hwaddr, MACADDRLEN);
	txinsn.hdr_len = WLAN_HDR_A3_QOS_LEN;

	memset(tempQosControl, 0, 2);
	tempQosControl[0] = 0x07;		//set priority to VO
	tempQosControl[0] |= BIT(4);	//set EOSP
	memcpy((void *)GetQosControl((txinsn.phdr)), tempQosControl, 2);

	if ((rtl8192cd_firetx(priv, &txinsn)) == SUCCESS)
		return;

send_qos_null_fail:

	if (txinsn.phdr)
		release_wlanhdr_to_poll(priv, txinsn.phdr);
}


static void process_APSD_dz_queue(struct rtl8192cd_priv *priv, struct stat_info *pstat, unsigned short tid)
{
	unsigned int deque_level = 1;		// deque pkts level, VO = 4, VI = 3, BE = 2, BK = 1
	struct sk_buff *pskb = NULL;
	DECLARE_TXINSN(txinsn);

	if ((((tid == 7) || (tid == 6)) && !(pstat->apsd_bitmap & 0x01))
		|| (((tid == 5) || (tid == 4)) && !(pstat->apsd_bitmap & 0x02))
		|| (((tid == 3) || (tid == 0)) && !(pstat->apsd_bitmap & 0x08))
		|| (((tid == 2) || (tid == 1)) && !(pstat->apsd_bitmap & 0x04))) {
		DEBUG_INFO("RcvQosNull legacy ps tid=%d", tid);
		return;
	}

	if (pstat->apsd_pkt_buffering == 0)
		goto sendQosNull;

	if ((pstat->apsd_bitmap & 0x01) && (!isFFempty(pstat->VO_dz_queue->head, pstat->VO_dz_queue->tail)))
		deque_level = 4;
	else if ((pstat->apsd_bitmap & 0x02) && (!isFFempty(pstat->VI_dz_queue->head, pstat->VI_dz_queue->tail)))
		deque_level = 3;
	else if ((pstat->apsd_bitmap & 0x08) && (!isFFempty(pstat->BE_dz_queue->head, pstat->BE_dz_queue->tail)))
		deque_level = 2;
	else if ((!(pstat->apsd_bitmap & 0x04)) || (isFFempty(pstat->BK_dz_queue->head, pstat->BK_dz_queue->tail))) {
		//send QoS Null packet
sendQosNull:
		SendQosNullData(priv, pstat->hwaddr);
		DEBUG_INFO("sendQosNull  tid=%d\n", tid);
		return;
	}

	while(1) {
		if (deque_level == 4) {
			pskb = (struct sk_buff *)deque(priv, &(pstat->VO_dz_queue->head), &(pstat->VO_dz_queue->tail),
					(unsigned int)(pstat->VO_dz_queue->pSkb), NUM_APSD_TXPKT_QUEUE);
			if (pskb == NULL) {
				if ((pstat->apsd_bitmap & 0x02) && (!isFFempty(pstat->VI_dz_queue->head, pstat->VI_dz_queue->tail)))
					deque_level--;
				else if ((pstat->apsd_bitmap & 0x08) && (!isFFempty(pstat->BE_dz_queue->head, pstat->BE_dz_queue->tail)))
					deque_level = 2;
				else if ((pstat->apsd_bitmap & 0x04) && (!isFFempty(pstat->BK_dz_queue->head, pstat->BK_dz_queue->tail)))
					deque_level = 1;
				else
					deque_level = 0;
			}
			else {
				DEBUG_INFO("deque VO pkt\n");
			}
		}
		else if (deque_level == 3) {
			pskb = (struct sk_buff *)deque(priv, &(pstat->VI_dz_queue->head), &(pstat->VI_dz_queue->tail),
					(unsigned int)(pstat->VI_dz_queue->pSkb), NUM_APSD_TXPKT_QUEUE);
			if (pskb == NULL) {
				if ((pstat->apsd_bitmap & 0x08) && (!isFFempty(pstat->BE_dz_queue->head, pstat->BE_dz_queue->tail)))
					deque_level--;
				else if ((pstat->apsd_bitmap & 0x04) && (!isFFempty(pstat->BK_dz_queue->head, pstat->BK_dz_queue->tail)))
					deque_level = 1;
				else
					deque_level = 0;
			}
			else {
				DEBUG_INFO("deque VI pkt\n");
			}
		}
		else if (deque_level == 2) {
			pskb = (struct sk_buff *)deque(priv, &(pstat->BE_dz_queue->head), &(pstat->BE_dz_queue->tail),
					(unsigned int)(pstat->BE_dz_queue->pSkb), NUM_APSD_TXPKT_QUEUE);
			if (pskb == NULL) {
				if ((pstat->apsd_bitmap & 0x04) && (!isFFempty(pstat->BK_dz_queue->head, pstat->BK_dz_queue->tail)))
					deque_level--;
				else
					deque_level = 0;
			}
			else {
				DEBUG_INFO("deque BE pkt\n");
			}
		}
		else if (deque_level == 1) {
			pskb = (struct sk_buff *)deque(priv, &(pstat->BK_dz_queue->head), &(pstat->BK_dz_queue->tail),
					(unsigned int)(pstat->BK_dz_queue->pSkb), NUM_APSD_TXPKT_QUEUE);
			if(pskb)
				DEBUG_INFO("deque BK pkt\n");
		}

		if (pskb) {
			txinsn.q_num   = BE_QUEUE;
			txinsn.fr_type = _SKB_FRAME_TYPE_;
			txinsn.pframe  = pskb;
			txinsn.phdr	   = (UINT8 *)get_wlanllchdr_from_poll(priv);
			pskb->cb[1] = 0;

			if (pskb->len > priv->pmib->dot11OperationEntry.dot11RTSThreshold)
				txinsn.retry = priv->pmib->dot11OperationEntry.dot11LongRetryLimit;
			else
				txinsn.retry = priv->pmib->dot11OperationEntry.dot11ShortRetryLimit;

			if (txinsn.phdr == NULL) {
				DEBUG_ERR("Can't alloc wlan header!\n");
				goto xmit_skb_fail;
			}

			memset((void *)txinsn.phdr, 0, sizeof(struct wlanllc_hdr));

			SetFrDs(txinsn.phdr);
			SetFrameSubType(txinsn.phdr, WIFI_QOS_DATA);
			if (((deque_level == 4) && (!isFFempty(pstat->VO_dz_queue->head, pstat->VO_dz_queue->tail)) && (pstat->apsd_bitmap & 0x01)) ||
				((deque_level >= 3) && (!isFFempty(pstat->VI_dz_queue->head, pstat->VI_dz_queue->tail)) && (pstat->apsd_bitmap & 0x02)) ||
				((deque_level >= 2) && (!isFFempty(pstat->BE_dz_queue->head, pstat->BE_dz_queue->tail)) && (pstat->apsd_bitmap & 0x08)) ||
				((deque_level >= 1) && (!isFFempty(pstat->BK_dz_queue->head, pstat->BK_dz_queue->tail)) && (pstat->apsd_bitmap & 0x04)))
				SetMData(txinsn.phdr);

			if (rtl8192cd_wlantx(priv, &txinsn) == CONGESTED) {

xmit_skb_fail:
				priv->ext_stats.tx_drops++;
				DEBUG_WARN("TX DROP: Congested!\n");
				if (txinsn.phdr)
					release_wlanllchdr_to_poll(priv, txinsn.phdr);
				if (pskb)
					rtl_kfree_skb(priv, pskb, _SKB_TX_);
			}
		}
		else if (deque_level <= 1) {
			if ((pstat->apsd_pkt_buffering) &&
				(isFFempty(pstat->VO_dz_queue->head, pstat->VO_dz_queue->tail)) &&
				(isFFempty(pstat->VI_dz_queue->head, pstat->VI_dz_queue->tail)) &&
				(isFFempty(pstat->BE_dz_queue->head, pstat->BE_dz_queue->tail)) &&
				(isFFempty(pstat->BK_dz_queue->head, pstat->BK_dz_queue->tail)))
				pstat->apsd_pkt_buffering = 0;
			break;
		}
	}
}


static void process_qos_null(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	unsigned char *pframe;
	struct stat_info *pstat = NULL;

	pframe  = get_pframe(pfrinfo);
	pstat = get_stainfo(priv, get_sa(pframe));

	if ((!(OPMODE & WIFI_AP_STATE)) || (pstat == NULL)) {
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
		return;
	}
	process_APSD_dz_queue(priv, pstat, pfrinfo->tid);

	rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
}
#endif


#if defined(DRVMAC_LB) && defined(WIFI_WMM)
static void process_lb_qos_null(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
//	unsigned char *pframe;
//	unsigned int aid;
//	struct stat_info *pstat = NULL;

//	pframe  = get_pframe(pfrinfo);
//	aid = GetAid(pframe);
//	pstat = get_aidinfo(priv, aid);

//	if ((!(OPMODE & WIFI_AP_STATE)) || (pstat == NULL) || (memcmp(pstat->hwaddr, get_sa(pframe), MACADDRLEN))) {
//		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
//		return;
//	}
//	process_APSD_dz_queue(priv, pstat, pfrinfo->tid);

#if 0
	if (pfrinfo->pskb && pfrinfo->pskb->data) {
		unsigned int *p_skb_int = (unsigned int *)pfrinfo->pskb->data;
		printk("LB RX FRAME =====>>\n");
		printk("0x%08x 0x%08x 0x%08x 0x%08x\n", *p_skb_int, *(p_skb_int+1), *(p_skb_int+2), *(p_skb_int+3));
		printk("0x%08x 0x%08x 0x%08x 0x%08x\n", *(p_skb_int+4), *(p_skb_int+5), *(p_skb_int+6), *(p_skb_int+7));
		printk("LB RX FRAME <<=====\n");
	}
#endif
	rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
}


static void process_lb_qos(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
//	unsigned char *pframe;
//	unsigned int aid;
//	struct stat_info *pstat = NULL;

//	pframe  = get_pframe(pfrinfo);
//	aid = GetAid(pframe);
//	pstat = get_aidinfo(priv, aid);

//	if ((!(OPMODE & WIFI_AP_STATE)) || (pstat == NULL) || (memcmp(pstat->hwaddr, get_sa(pframe), MACADDRLEN))) {
//		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
//		return;
//	}
//	process_APSD_dz_queue(priv, pstat, pfrinfo->tid);

#if 1
	if (pfrinfo->pskb && pfrinfo->pskb->data) {
		unsigned char *p_skb_int = (unsigned char *)pfrinfo->pskb->data;
		unsigned int payload_length = 0, i = 0, mismatch = 0;
		unsigned char matching = 0;

		if (pfrinfo->pktlen && pfrinfo->hdr_len && pfrinfo->pktlen > pfrinfo->hdr_len) {
			payload_length = pfrinfo->pktlen - pfrinfo->hdr_len;
			if (payload_length >= 2048)
				printk("LB Qos RX, payload max hit!\n");
//			if (payload_length > 32)
//				payload_length = 32;
		}
		else {
			if (!pfrinfo->pktlen)
				printk("LB Qos RX, zero pktlen!!!\n");
			else if (!pfrinfo->hdr_len)
				printk("LB Qos RX, zero hdr_len!!!\n");
			else if (pfrinfo->pktlen < pfrinfo->hdr_len)
				printk("LB Qos RX, pktlen < hdr_len!!!\n");
			else
				printk("LB Qos RX, empty payload.\n");
			goto out;
		}

		p_skb_int += pfrinfo->hdr_len;

//		printk("LB RX >> ");
//		for (i = 0; i < payload_length; i++) {
//			if (i>0 && !(i%4))
//				printk(" ");
//			if (!(i%4))
//				printk("0x");
//			printk("%02x", *(p_skb_int+i));
//		}
//		printk(" <<\n");

		for (i = 0; i < payload_length; i++) {
			if (priv->pmib->miscEntry.lb_mlmp == 1) {
				matching = 0;
				if (memcmp((p_skb_int+i), &matching, 1)) {
					mismatch++;
					break;
				}
			}
			else if (priv->pmib->miscEntry.lb_mlmp == 2) {
				matching = 0xff;
				if (memcmp((p_skb_int+i), &matching, 1)) {
					mismatch++;
					break;
				}
			}
			else if ((priv->pmib->miscEntry.lb_mlmp == 3) || (priv->pmib->miscEntry.lb_mlmp == 4)) {
				matching = i%0x100;
				if (memcmp((p_skb_int+i), &matching, 1)) {
					mismatch++;
					break;
				}
			}
			else {
				printk("LB Qos RX, wrong mlmp setting!\n");
				goto out;
			}
		}

		if (mismatch) {
			printk("LB Qos RX, rx pattern mismatch!!\n");
			priv->pmib->miscEntry.drvmac_lb = 0;	// stop the test
		}
	}
#endif

out:
	rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
}
#endif


#ifdef CONFIG_RTL8186_KB
int rtl8192cd_guestmac_valid(struct rtl8192cd_priv *priv, char *macaddr)
{
	int i=0;

	for (i=0; i<MAX_GUEST_NUM; i++)
	{
		if (priv->guestMac[i].valid && !memcmp(priv->guestMac[i].macaddr, macaddr, 6))
			return 1;
	}
	return 0;
}
#endif
#if defined (__LINUX_2_6__) && defined (CONFIG_RTL_IGMP_SNOOPING)
	/*added by qinjunjie,to avoid igmpv1/v2 report suppress*/
int rtl8192cd_isIgmpV1V2Report(unsigned char *macFrame)
{
	unsigned char *ptr;
	struct iphdr *iph=NULL;
	unsigned int payloadLen;

	if((macFrame[0]!=0x01) || (macFrame[1]!=0x00) || (macFrame[2]!=0x5e))
	{
		return FALSE;
	}

	ptr=macFrame+12;
	if(*(int16 *)(ptr)==(int16)htons(0x8100))
	{
		ptr=ptr+4;
	}

	/*it's not ipv4 packet*/
	if(*(int16 *)(ptr)!=(int16)htons(0x0800))
	{
		return FALSE;
	}
	ptr=(ptr+2);
	iph=(struct iphdr *)ptr;

	if(iph->protocol!=0x02)
	{
		return FALSE;
	}

	payloadLen=(iph->tot_len-((iph->ihl&0x0f)<<2));
	if(payloadLen>8)
	{
		return FALSE;
	}

	ptr=ptr+(((unsigned int)iph->ihl)<<2);
	if((*ptr==0x11) ||(*ptr==0x16))
	{
		return TRUE;
	}
	return FALSE;

}
#if defined (CONFIG_RTL_MLD_SNOOPING)
#define IPV6_ROUTER_ALTER_OPTION 0x05020000
#define  HOP_BY_HOP_OPTIONS_HEADER 0
#define ROUTING_HEADER 43
#define  FRAGMENT_HEADER 44
#define DESTINATION_OPTION_HEADER 60

#define ICMP_PROTOCOL 58

#define MLD_QUERY 130
#define MLDV1_REPORT 131
#define MLDV1_DONE 132
#define MLDV2_REPORT 143

int rtl8192cd_isMldV1Report(unsigned char *macFrame)
{
	unsigned char *ptr;
	struct ipv6hdr* ipv6h;
	unsigned char *startPtr=NULL;
	unsigned char *lastPtr=NULL;
	unsigned char nextHeader=0;
	unsigned short extensionHdrLen=0;

	unsigned char  optionDataLen=0;
	unsigned char  optionType=0;
	unsigned int ipv6RAO=0;

	if((macFrame[0]!=0x33) || (macFrame[1]!=0x33) )
	{
		return FALSE;
	}

	if(macFrame[2]==0xff)
	{
		return FALSE;
	}

	ptr=macFrame+12;
	if(*(int16 *)(ptr)==(int16)htons(0x8100))
	{
		ptr=ptr+4;
	}

	/*it's not ipv6 packet*/
	if(*(int16 *)(ptr)!=(int16)htons(0x86dd))
	{
		return FALSE;
	}

	ptr=(ptr+2);

	ipv6h= (struct ipv6hdr *) ptr;
	if(ipv6h->version!=6)
	{
		return FALSE;
	}

	startPtr= (unsigned char *)ptr;
	lastPtr=startPtr+sizeof(struct ipv6hdr)+(ipv6h->payload_len);
	nextHeader= ipv6h ->nexthdr;
	ptr=startPtr+sizeof(struct ipv6hdr);

	while(ptr<lastPtr)
	{
		switch(nextHeader)
		{
			case HOP_BY_HOP_OPTIONS_HEADER:
				/*parse hop-by-hop option*/
				nextHeader=ptr[0];
				extensionHdrLen=((uint16)(ptr[1])+1)*8;
				ptr=ptr+2;

				while(ptr<(startPtr+extensionHdrLen+sizeof(struct ipv6hdr)))
				{
					optionType=ptr[0];
					/*pad1 option*/
					if(optionType==0)
					{
						ptr=ptr+1;
						continue;
					}

					/*padN option*/
					if(optionType==1)
					{
						optionDataLen=ptr[1];
						ptr=ptr+optionDataLen+2;
						continue;
					}

					/*router altert option*/
					if(ntohl(*(uint32 *)(ptr))==IPV6_ROUTER_ALTER_OPTION)
					{
						ipv6RAO=IPV6_ROUTER_ALTER_OPTION;
						ptr=ptr+4;
						continue;
					}

					/*other TLV option*/
					if((optionType!=0) && (optionType!=1))
					{
						optionDataLen=ptr[1];
						ptr=ptr+optionDataLen+2;
						continue;
					}


				}

				break;

			case ROUTING_HEADER:
				nextHeader=ptr[0];
				extensionHdrLen=((uint16)(ptr[1])+1)*8;
                            	ptr=ptr+extensionHdrLen;
				break;

			case FRAGMENT_HEADER:
				nextHeader=ptr[0];
				ptr=ptr+8;
				break;

			case DESTINATION_OPTION_HEADER:
				nextHeader=ptr[0];
				extensionHdrLen=((uint16)(ptr[1])+1)*8;
				ptr=ptr+extensionHdrLen;
				break;

			case ICMP_PROTOCOL:
				if(ptr[0]==MLDV1_REPORT)
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
				break;

			default:
				return FALSE;
		}

	}
	return FALSE;

}
#endif
#endif


static int process_datafrme(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	unsigned char 	*pframe, da[MACADDRLEN];
	unsigned int  	privacy;
	unsigned int	res;
	struct stat_info *pstat = NULL, *dst_pstat = NULL;
	struct sk_buff	 *pskb  = NULL, *pnewskb = NULL;
	unsigned char	qosControl[2];
	int dontBcast2otherSta = 0, do_rc = 0;

	pframe = get_pframe(pfrinfo);

	pskb = get_pskb(pfrinfo);
	//skb_put(pskb, pfrinfo->pktlen);	// pskb->tail will be wrong
	pskb->tail = pskb->data + pfrinfo->pktlen;
	pskb->len = pfrinfo->pktlen;
	pskb->dev = priv->dev;

	if ((priv->pmib->dot11BssType.net_work_type & WIRELESS_11N) &&
		priv->pmib->reorderCtrlEntry.ReorderCtrlEnable) {
		if (!IS_MCAST(GetAddr1Ptr(pframe)))
			do_rc = 1;
	}

	if (OPMODE & WIFI_AP_STATE)
	{
		memcpy(da, pfrinfo->da, MACADDRLEN);

#ifdef CONFIG_RTK_MESH
		if (pfrinfo->is_11s)
		{
			pstat = get_stainfo(priv, GetAddr2Ptr(pframe));
			rx_sum_up(NULL, pstat, pfrinfo->pktlen, 0);
#ifdef USE_OUT_SRC	
			priv->pshare->NumRxBytesUnicast += pfrinfo->pktlen;
#endif			
			update_sta_rssi(priv, pstat, pfrinfo);
			return process_11s_datafrme(priv,pfrinfo, pstat);
		}
		else
#endif
#ifdef WDS
		if (pfrinfo->to_fr_ds == 3) {
			pstat = get_stainfo(priv, GetAddr2Ptr(pframe));
			pskb->dev = getWdsDevByAddr(priv, GetAddr2Ptr(pframe));
		}
		else
#endif
		{
#ifdef A4_STA
			if (pfrinfo->to_fr_ds == 3 &&  priv->pshare->rf_ft_var.a4_enable)
				pstat = get_stainfo(priv, GetAddr2Ptr(pframe));
			else
#endif
		                pstat = get_stainfo(priv, pfrinfo->sa);
		}

#if defined(CONFIG_RTL_WAPI_SUPPORT)
		if (wapiHandleRecvPacket(pfrinfo, pstat)==SUCCESS)
		{
			return SUCCESS;
		}

#endif

		// log rx statistics...
#ifdef  WDS
		if (pfrinfo->to_fr_ds == 3) {
			privacy = priv->pmib->dot11WdsInfo.wdsPrivacy;
		}
		else
#endif
		{
			privacy = get_sta_encrypt_algthm(priv, pstat);
		}
		rx_sum_up(NULL, pstat, pfrinfo->pktlen, 0);
#ifdef USE_OUT_SRC	
		priv->pshare->NumRxBytesUnicast += pfrinfo->pktlen;
#endif		
		update_sta_rssi(priv, pstat, pfrinfo);

#ifdef DETECT_STA_EXISTANCE
#ifdef CONFIG_RTL_88E_SUPPORT
        if (GET_CHIP_VER(priv)==VERSION_8188E) {
            if (pstat->leave!= 0)
                RTL8188E_MACID_NOLINK(priv, 0, REMAP_AID(pstat));
        }
#endif
		pstat->leave = 0;
#endif

#ifdef SUPPORT_SNMP_MIB
		if (IS_MCAST(da))
			SNMP_MIB_INC(dot11MulticastReceivedFrameCount, 1);
#endif

#if defined(WIFI_WMM) && defined(WMM_APSD)
		if(
#ifdef CLIENT_MODE
			(OPMODE & WIFI_AP_STATE) &&
#endif
			(QOS_ENABLE) && (APSD_ENABLE) && (pstat->QosEnabled) && (pstat->apsd_bitmap & 0x0f) &&
			((pstat->state & (WIFI_ASOC_STATE|WIFI_SLEEP_STATE)) == (WIFI_ASOC_STATE|WIFI_SLEEP_STATE)) &&
			(GetFrameSubType(get_pframe(pfrinfo)) == (WIFI_QOS_DATA))) {
			process_APSD_dz_queue(priv, pstat, pfrinfo->tid);
		}
#endif

		// Process A-MSDU
		if (is_qos_data(pframe)) {
			memcpy(qosControl, GetQosControl(pframe), 2);
			if (qosControl[0] & BIT(7))	// A-MSDU present
			{
#ifdef USE_OUT_SRC			
				if (!pstat->is_realtek_sta && (pstat->IOTPeer!=HT_IOT_PEER_RALINK) && (pstat->IOTPeer!=HT_IOT_PEER_MARVELL)) {
					pstat->IOTPeer=HT_IOT_PEER_MARVELL;
#else
				if (!pstat->is_realtek_sta && !pstat->is_ralink_sta && !pstat->is_marvell_sta) {
					pstat->is_marvell_sta = 1;
#endif					
					if (priv->pshare->is_40m_bw){
#ifdef STA_EXT
						if (pstat->aid > FW_NUM_STAT)
							priv->pshare->marvellMapBitExt |= BIT(pstat->aid - FW_NUM_STAT - 1);
						else
#endif
#ifdef CONFIG_RTL_88E_SUPPORT
						if ((GET_CHIP_VER(priv) == VERSION_8188E) && (pstat->aid > 32))
							priv->pshare->marvellMapBit_88e_hw_ext |= BIT(pstat->aid - 32 - 1);
						else
#endif
							priv->pshare->marvellMapBit |= BIT(pstat->aid - 1);
					}
				}
#ifdef USE_OUT_SRC
				if (priv->pshare->is_40m_bw && (pstat->IOTPeer==HT_IOT_PEER_MARVELL) && (priv->pshare->Reg_RRSR_2 == 0) && (priv->pshare->Reg_81b == 0)){
#else
				if (priv->pshare->is_40m_bw && pstat->is_marvell_sta && (priv->pshare->Reg_RRSR_2 == 0) && (priv->pshare->Reg_81b == 0)){
#endif					
					priv->pshare->Reg_RRSR_2 = RTL_R8(RRSR+2);
					priv->pshare->Reg_81b = RTL_R8(0x81b);
					RTL_W8(RRSR+2, priv->pshare->Reg_RRSR_2 | 0x60);
					RTL_W8(0x81b, priv->pshare->Reg_81b | 0x0E);
				}

				process_amsdu(priv, pstat, pfrinfo);
				return SUCCESS;
			}
#ifdef RX_BUFFER_GATHER
			else if (!list_empty(&priv->pshare->gather_list))
				flush_rx_list(priv);
#endif
		}

#ifdef PREVENT_BROADCAST_STORM
//			if (get_free_memory() < FREE_MEM_LOWER_BOUND) {
			if (da[0] == 0xff) {
				pstat->rx_pkts_bc++;
				#if 0
				if (pstat->rx_pkts_bc > BROADCAST_STORM_THRESHOLD) {
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: Broadcast storm happened!\n");
					return FAIL;
				}
				#endif
			}
#endif

		// AP always receive unicast frame only
#ifdef WDS
		if (pfrinfo->to_fr_ds!=3 && IS_MCAST(da))
#else
		if (IS_MCAST(da))
#endif
		{
#ifdef DRVMAC_LB
			if (priv->pmib->miscEntry.drvmac_lb) {
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: drop br/ml packet in loop-back mode!\n");
				return FAIL;
			}
#endif

#if 0 //def PREVENT_BROADCAST_STORM
//			if (get_free_memory() < FREE_MEM_LOWER_BOUND) {
			if (da[0] == 0xff) {
				pstat->rx_pkts_bc++;
				if (pstat->rx_pkts_bc > BROADCAST_STORM_THRESHOLD) {
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: Broadcast storm happened!\n");
					return FAIL;
				}
			}
#endif

			// This is a legal frame, convert it to skb
			res = skb_p80211_to_ether(priv->dev, privacy, pfrinfo);
			if (res == FAIL) {
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: skb_p80211_to_ether fail!\n");
				return FAIL;
			}

#ifdef  SUPPORT_TX_MCAST2UNI
			 if(IP_MCAST_MAC(pskb->data) && IS_IGMP_PROTO(pskb->data))
			 {
				dontBcast2otherSta=1;
			 }
#endif

#if defined (__LINUX_2_6__) && defined (CONFIG_RTL_IGMP_SNOOPING)
			/*added by qinjunjie,to avoid igmpv1/v2 report suppress*/
			 if(rtl8192cd_isIgmpV1V2Report(pskb->data))
			 {
			 	//printk("%s:%d,receive igmpv1/v2 report\n",__FUNCTION__,__LINE__);
				goto  mcast_netif_rx;
			 }
			#if defined (CONFIG_RTL_MLD_SNOOPING)
			if(rtl8192cd_isMldV1Report(pskb->data))
			{
				goto  mcast_netif_rx;
			}
			#endif
#endif

#ifdef  SUPPORT_TX_MCAST2UNI
			 if(IP_MCAST_MAC(pskb->data) && IS_IGMP_PROTO(pskb->data))
			 {
				dontBcast2otherSta=1;
			 }
#endif


#ifdef __KERNEL__
			// if we are STP aware, don't broadcast received BPDU
			if (!(priv->dev->br_port &&
				 priv->dev->br_port->br->stp_enabled &&
				 !memcmp(pskb->data, "\x01\x80\xc2\x00\x00\x00", 6)))
#endif
			{
				if (!priv->pmib->dot11OperationEntry.block_relay)
				{
#if defined(_SINUX_) && defined(CONFIG_RTL865X_ETH_PRIV_SKB)
					extern struct sk_buff *priv_skb_copy(struct sk_buff *skb);
					pnewskb = priv_skb_copy(pskb);
#else
					pnewskb = skb_copy(pskb, GFP_ATOMIC);
#endif
					if (pnewskb) {
#ifdef GBWC
						if (GBWC_forward_check(priv, pnewskb, pstat)) {
							// packet is queued, nothing to do
						}
						else
#endif
						{
#ifdef TX_SCATTER
							pnewskb->list_num = 0;
#endif
							if(dontBcast2otherSta){
								rtl_kfree_skb(priv, pnewskb, _SKB_TX_);
							}else{
#ifdef PREVENT_BROADCAST_STORM
	                            if (da[0] == 0xff) {
		                            if (pstat->rx_pkts_bc > BROADCAST_STORM_THRESHOLD) {
			                            priv->ext_stats.rx_data_drops++;
				                        DEBUG_ERR("RX DROP: Broadcast storm happened!\n");
					                    rtl_kfree_skb(priv, pnewskb, _SKB_TX_);
						            }
							        else {
								        if (rtl8192cd_start_xmit(pnewskb, priv->dev))
									        rtl_kfree_skb(priv, pnewskb, _SKB_TX_);
	                                }
		                        }
			                    else
#endif
								{
									if (rtl8192cd_start_xmit(pnewskb, priv->dev))
										rtl_kfree_skb(priv, pnewskb, _SKB_TX_);
								}
							}

						}
					}
				}
			}

#if defined (__LINUX_2_6__) && defined (CONFIG_RTL_IGMP_SNOOPING)
mcast_netif_rx:
#endif

			if (do_rc) {
				*(unsigned int *)&(pfrinfo->pskb->cb[4]) = 0;
				if (reorder_ctrl_check(priv, pstat, pfrinfo))
					rtl_netif_rx(priv, pfrinfo->pskb, pstat);
			}
			else
				rtl_netif_rx(priv, pskb, pstat);
		}
		else
		{
			// unicast.. the status of sa has been checked in defrag_frame.
			// however, we should check if the da is in the WDS to see if we should
			res = skb_p80211_to_ether(pskb->dev, privacy, pfrinfo);
			if (res == FAIL) {
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: skb_p80211_to_ether fail!\n");
				return FAIL;
			}

			dst_pstat = get_stainfo(priv, da);

#ifdef A4_STA
			if (priv->pshare->rf_ft_var.a4_enable && (dst_pstat == NULL))
				dst_pstat = a4_sta_lookup(priv, da);
#endif

#ifdef WDS
			if ((pfrinfo->to_fr_ds==3) ||
				(dst_pstat == NULL) || !(dst_pstat->state & WIFI_ASOC_STATE))
#else
			if ((dst_pstat == NULL) || (!(dst_pstat->state & WIFI_ASOC_STATE)))
#endif
			{
#ifndef __ECOS
				if (priv->pmib->dot11OperationEntry.guest_access
#ifdef CONFIG_RTL8186_KB
						||(pstat && pstat->ieee8021x_ctrlport == DOT11_PortStatus_Guest)
#endif
					) {
					if (
#ifdef LINUX_2_6_22_
						(*(unsigned short *)(pskb->mac_header + MACADDRLEN*2) != __constant_htons(0x888e)) &&
						(*(unsigned short *)(pskb->mac_header + MACADDRLEN*2) != __constant_htons(0x86dd)) &&
#else
						(*(unsigned short *)(pskb->mac.raw + MACADDRLEN*2) != __constant_htons(0x888e)) &&
						(*(unsigned short *)(pskb->mac.raw + MACADDRLEN*2) != __constant_htons(0x86dd)) &&
#endif
						priv->dev->br_port &&
#ifdef __LINUX_2_6__
	#if defined(CONFIG_RTL_EAP_RELAY) || defined(CONFIG_RTK_INBAND_HOST_HACK)
						memcmp(da, inband_Hostmac, MACADDRLEN)
	#else
						memcmp(da, priv->dev->br_port->br->dev->dev_addr, MACADDRLEN)
	#endif
#else
						memcmp(da, priv->dev->br_port->br->dev.dev_addr, MACADDRLEN)
#endif
						) {
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: guest access fail!\n");
						return FAIL;
					}
#ifndef NOT_RTK_BSP
					pskb->__unused = 0xe5;
#endif

#ifdef CONFIG_RTL8186_KB
					if (priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm == 0)
					{
						/* hotel style guest access */
						if (!rtl8192cd_guestmac_valid(priv, pskb->mac.raw+MACADDRLEN))
						{
#ifndef NOT_RTK_BSP
							pskb->__unused = 0xd3;
#endif
						}
					}
					else
					{
						/* wpa/wp2 guest access */
						/* just let __unused flag be 0xe5 */
					}
#endif

					//printk("guest packet, addr: %0x2:%02x:%02x:%02x:%02x:%02x\n",da[0],da[1],da[2],da[3],da[4],da[5]);
				}
#ifndef NOT_RTK_BSP
				else
					pskb->__unused = 0;
#endif

#ifdef __LINUX_2_6__
				if(pskb->dev)
					pskb->protocol = eth_type_trans(pskb,pskb->dev);
				else
#endif
					pskb->protocol = eth_type_trans(pskb, priv->dev);

#ifdef EAPOLSTART_BY_QUEUE
#ifdef LINUX_2_6_22_
				if (*(unsigned short *)(pskb->mac_header + MACADDRLEN*2) == __constant_htons(0x888e))
#else
				if (*(unsigned short *)(pskb->mac.raw + MACADDRLEN*2) == __constant_htons(0x888e))
#endif
				{
					unsigned char		szEAPOL[] = {0x01, 0x01, 0x00, 0x00};
					DOT11_EAPOL_START	Eapol_Start;

					if (!memcmp(pskb->data, szEAPOL, sizeof(szEAPOL)))
					{
						Eapol_Start.EventId = DOT11_EVENT_EAPOLSTART;
						Eapol_Start.IsMoreEvent = FALSE;
#ifdef LINUX_2_6_22_
						memcpy(&Eapol_Start.MACAddr, pskb->mac_header + MACADDRLEN, WLAN_ETHHDR_LEN);
#else
						memcpy(&Eapol_Start.MACAddr, pskb->mac.raw + MACADDRLEN, WLAN_ETHHDR_LEN);
#endif
						DOT11_EnQueue((unsigned long)priv, priv->pevent_queue, (unsigned char*)&Eapol_Start, sizeof(DOT11_EAPOL_START));
#ifdef LINUX_2_6_22_
						event_indicate(priv, pskb->mac_header + MACADDRLEN, 4);
#else
						event_indicate(priv, pskb->mac.raw + MACADDRLEN, 4);
#endif
						return FAIL;	// let dsr free this skb
					}
				}
#endif
#endif /* __ECOS */

#if (defined(EAP_BY_QUEUE) || defined(INCLUDE_WPA_PSK)) && (!defined(WIFI_HAPD) || defined(HAPD_DRV_PSK_WPS))
#ifdef WDS
#ifdef LINUX_2_6_22_
				if ((pfrinfo->to_fr_ds != 3) && (*(unsigned short *)(pskb->mac_header + MACADDRLEN*2) == __constant_htons(0x888e)))
#else
#ifdef __ECOS
				if ((pfrinfo->to_fr_ds != 3) && (*(unsigned short *)(pskb->data + MACADDRLEN*2) == __constant_htons(0x888e)))
#else
				if ((pfrinfo->to_fr_ds != 3) && (*(unsigned short *)(pskb->mac.raw + MACADDRLEN*2) == __constant_htons(0x888e)))
#endif
#endif
#else
#ifdef LINUX_2_6_22_
				if (*(unsigned short *)(pskb->mac_header + MACADDRLEN*2) == __constant_htons(0x888e))
#else
#ifdef __ECOS
				if (*(unsigned short *)(pskb->data + MACADDRLEN*2) == __constant_htons(0x888e))
#else
				if (*(unsigned short *)(pskb->mac.raw + MACADDRLEN*2) == __constant_htons(0x888e))
#endif
#endif
#endif
				{
					if (IEEE8021X_FUN
#ifdef INCLUDE_WPA_PSK
							|| (priv->pmib->dot1180211AuthEntry.dot11EnablePSK &&
									((priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm == _TKIP_PRIVACY_) ||
									 (priv->pmib->dot1180211AuthEntry.dot11PrivacyAlgrthm == _CCMP_PRIVACY_)))
#endif
#ifdef WIFI_SIMPLE_CONFIG
							|| priv->pmib->wscEntry.wsc_enable
#endif
					) {
						unsigned short		pkt_len;

						pkt_len = WLAN_ETHHDR_LEN + pskb->len;
						priv->Eap_packet->EventId = DOT11_EVENT_EAP_PACKET;
						priv->Eap_packet->IsMoreEvent = FALSE;
						memcpy(&(priv->Eap_packet->packet_len), &pkt_len, sizeof(unsigned short));
#ifdef __ECOS
						memcpy(&priv->Eap_packet->packet, pskb->data, pskb->len);
						pskb->len -= WLAN_ETHHDR_LEN;
#else
#ifdef LINUX_2_6_22_
						memcpy(&(priv->Eap_packet->packet[0]), pskb->mac_header, WLAN_ETHHDR_LEN);
#else
						memcpy(&(priv->Eap_packet->packet[0]), pskb->mac.raw, WLAN_ETHHDR_LEN);
#endif
						memcpy(&(priv->Eap_packet->packet[WLAN_ETHHDR_LEN]), pskb->data, pskb->len);
#endif
#ifdef EAP_BY_QUEUE

#ifdef INCLUDE_WPS

						wps_NonQueue_indicate_evt(priv ,
							(char *)priv->Eap_packet, sizeof(DOT11_EAP_PACKET));
#else
						DOT11_EnQueue((unsigned long)priv, priv->pevent_queue,
							(unsigned char*)priv->Eap_packet,sizeof(DOT11_EAP_PACKET));

						event_indicate(priv, NULL, -1);
#endif

#endif
#ifdef INCLUDE_WPA_PSK
						psk_indicate_evt(priv, DOT11_EVENT_EAP_PACKET, (unsigned char*)&(priv->Eap_packet->packet[6]),
							(unsigned char*)priv->Eap_packet->packet, WLAN_ETHHDR_LEN+pskb->len);
#endif

#ifndef WIFI_HAPD
						return FAIL;	// let dsr free this skb
#endif
					}
				}
#endif

#ifndef __ECOS
				skb_push(pskb, WLAN_ETHHDR_LEN);	// push back due to be pulled by eth_type_trans()
#endif

				if (do_rc) {
					*(unsigned int *)&(pfrinfo->pskb->cb[4]) = 0;
					if (reorder_ctrl_check(priv, pstat, pfrinfo))
						rtl_netif_rx(priv, pfrinfo->pskb, pstat);
				}
				else
					rtl_netif_rx(priv, pskb, pstat);
			}
			else
			{
				if (priv->pmib->dot11OperationEntry.block_relay == 1) {
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: Relay unicast packet is blocked!\n");
#ifdef RX_SHORTCUT
					if (dst_pstat->rx_payload_offset > 0) // shortcut data saved, clear it
						dst_pstat->rx_payload_offset = 0;
#endif
					return FAIL;
				}
				else if (priv->pmib->dot11OperationEntry.block_relay == 2) {
					DEBUG_INFO("Relay unicast packet is blocked! Indicate to bridge.\n");
					rtl_netif_rx(priv, pskb, pstat);
				}
				else {
#ifdef ENABLE_RTL_SKB_STATS
					rtl_atomic_dec(&priv->rtl_rx_skb_cnt);
#endif
#ifdef GBWC
					if (GBWC_forward_check(priv, pfrinfo->pskb, pstat)) {
						// packet is queued, nothing to do
					}
					else
#endif
					if (do_rc) {
						*(unsigned int *)&(pfrinfo->pskb->cb[4]) = (unsigned int)dst_pstat;	// backup pstat pointer
						if (reorder_ctrl_check(priv, pstat, pfrinfo)) {
#ifdef TX_SCATTER
							pskb->list_num = 0;
#endif
//Joule 2009.03.10
#ifdef CONFIG_RTK_MESH
							if (rtl8192cd_start_xmit(pskb, isMeshPoint(dst_pstat) ? priv->mesh_dev: priv->dev))
#else
							if (rtl8192cd_start_xmit(pskb, priv->dev))
#endif
								rtl_kfree_skb(priv, pskb, _SKB_RX_);
						}
					} else {
#ifdef TX_SCATTER
						pskb->list_num = 0;
#endif
#ifdef CONFIG_RTK_MESH
// mantis bug 0000081	2008.07.16
					if (rtl8192cd_start_xmit(pskb, isMeshPoint(dst_pstat) ? priv->mesh_dev: priv->dev))
#else
					if (rtl8192cd_start_xmit(pskb, priv->dev))
#endif
							rtl_kfree_skb(priv, pskb, _SKB_RX_);
					}
				}
			}
		}
	}
#ifdef CLIENT_MODE
	else if (OPMODE & (WIFI_STATION_STATE | WIFI_ADHOC_STATE)) {
		// I am station, and just report every frame I received to protocol statck
		if (OPMODE & WIFI_STATION_STATE)
			pstat = get_stainfo(priv, BSSID);
		else	// Ad-hoc
			pstat = get_stainfo(priv, pfrinfo->sa);

		if (IS_MCAST(pfrinfo->da)) {
			// iv, icv and mic are not be used below. Don't care them!
			privacy = get_mcast_encrypt_algthm(priv);
		} else {
			privacy = get_sta_encrypt_algthm(priv, pstat);
		}

		rx_sum_up(NULL, pstat, pfrinfo->pktlen, 0);
#ifdef USE_OUT_SRC	
		priv->pshare->NumRxBytesUnicast += pfrinfo->pktlen;
#endif
		update_sta_rssi(priv, pstat, pfrinfo);
		priv->rxDataNumInPeriod++;
		if (IS_MCAST(pfrinfo->da)) {
			priv->rxMlcstDataNumInPeriod++;
#ifdef SUPPORT_SNMP_MIB
			SNMP_MIB_INC(dot11MulticastReceivedFrameCount, 1);
#endif
		} else if ((OPMODE & WIFI_STATION_STATE) && (priv->ps_state)) {
			if ((GetFrameSubType(get_pframe(pfrinfo)) == WIFI_DATA)
#ifdef WIFI_WMM
				|| (QOS_ENABLE && pstat->QosEnabled && (GetFrameSubType(get_pframe(pfrinfo)) == WIFI_QOS_DATA))
#endif
			) {
				if (GetMData(pframe)) {
#if defined(WIFI_WMM) && defined(WMM_APSD)
					if (QOS_ENABLE && APSD_ENABLE && priv->uapsd_assoc) {
						if (!((priv->pmib->dot11QosEntry.UAPSD_AC_BE && ((pfrinfo->tid == 0) || (pfrinfo->tid == 3))) ||
							(priv->pmib->dot11QosEntry.UAPSD_AC_BK && ((pfrinfo->tid == 1) || (pfrinfo->tid == 2))) ||
							(priv->pmib->dot11QosEntry.UAPSD_AC_VI && ((pfrinfo->tid == 4) || (pfrinfo->tid == 5))) ||
							(priv->pmib->dot11QosEntry.UAPSD_AC_VO && ((pfrinfo->tid == 6) || (pfrinfo->tid == 7)))))
							issue_PsPoll(priv);
					} else
#endif
					{
						issue_PsPoll(priv);
					}
				}
			}
		}

		#if defined(CONFIG_RTL_WAPI_SUPPORT)
		if (privacy==_WAPI_SMS4_&&wapiHandleRecvPacket(pfrinfo, pstat)==SUCCESS)
		{
			return SUCCESS;
		}
		#endif
		// Process A-MSDU
		if (is_qos_data(pframe)) {
			memcpy(qosControl, GetQosControl(pframe), 2);
			if (qosControl[0] & BIT(7))	// A-MSDU present
			{
				process_amsdu(priv, pstat, pfrinfo);
				return SUCCESS;
			}
#ifdef RX_BUFFER_GATHER
			else if (!list_empty(&priv->pshare->gather_list))
				flush_rx_list(priv);
#endif
		}

		res = skb_p80211_to_ether(priv->dev, privacy, pfrinfo);
		if (res == FAIL) {
			priv->ext_stats.rx_data_drops++;
			DEBUG_ERR("RX DROP: skb_p80211_to_ether fail!\n");
			return FAIL;
		}

#ifdef RTK_BR_EXT
		if(nat25_handle_frame(priv, pskb) == -1) {
			priv->ext_stats.rx_data_drops++;
			DEBUG_ERR("RX DROP: nat25_handle_frame fail!\n");
			return FAIL;
		}
#endif

#ifdef __KERNEL__
		pskb->protocol = eth_type_trans(pskb, priv->dev);
#endif

#if (defined(EAP_BY_QUEUE) || defined(INCLUDE_WPA_PSK)) && (!defined(WIFI_HAPD) || defined(HAPD_DRV_PSK_WPS))
#ifdef LINUX_2_6_22_
		if (*(unsigned short *)(pskb->mac_header + MACADDRLEN*2) == __constant_htons(0x888e))
#else
#ifdef __ECOS
		if (*(unsigned short *)(pskb->data + MACADDRLEN*2) == __constant_htons(0x888e))
#else
		if (*(unsigned short *)(pskb->mac.raw + MACADDRLEN*2) == __constant_htons(0x888e))
#endif
#endif
		{
			unsigned short		pkt_len;

			pkt_len = WLAN_ETHHDR_LEN + pskb->len;
			priv->Eap_packet->EventId = DOT11_EVENT_EAP_PACKET;
			priv->Eap_packet->IsMoreEvent = FALSE;
			memcpy(&(priv->Eap_packet->packet_len), &pkt_len, sizeof(unsigned short));
#ifdef __ECOS
			memcpy(&priv->Eap_packet->packet, pskb->data, pskb->len);
			pskb->len -= WLAN_ETHHDR_LEN;
#else
#ifdef LINUX_2_6_22_
			memcpy(&(priv->Eap_packet->packet[0]), pskb->mac_header, WLAN_ETHHDR_LEN);
#else
			memcpy(&(priv->Eap_packet->packet[0]), pskb->mac.raw, WLAN_ETHHDR_LEN);
#endif
			memcpy(&(priv->Eap_packet->packet[WLAN_ETHHDR_LEN]), pskb->data, pskb->len);
#endif
#ifdef EAP_BY_QUEUE

#ifdef INCLUDE_WPS

			wps_NonQueue_indicate_evt(priv ,
				(char *)priv->Eap_packet, sizeof(DOT11_EAP_PACKET));
#else
			DOT11_EnQueue((unsigned long)priv, priv->pevent_queue,
				(unsigned char*)priv->Eap_packet, sizeof(DOT11_EAP_PACKET));
			event_indicate(priv, NULL, -1);
#endif
#endif
#ifdef INCLUDE_WPA_PSK
			psk_indicate_evt(priv, DOT11_EVENT_EAP_PACKET, (unsigned char*)&(priv->Eap_packet->packet[6]),
				(unsigned char*)priv->Eap_packet->packet, WLAN_ETHHDR_LEN+pskb->len);
#endif

#ifndef WIFI_HAPD
			return FAIL;	// let dsr free this skb
#endif
		}
#endif

#ifdef __KERNEL__
		skb_push(pskb, WLAN_ETHHDR_LEN);	// push back due to be pulled by eth_type_trans()
#endif
		if (do_rc) {
			*(unsigned int *)&(pfrinfo->pskb->cb[4]) = 0;
			if (reorder_ctrl_check(priv, pstat, pfrinfo))
				rtl_netif_rx(priv, pskb, pstat);
		}
		else
			rtl_netif_rx(priv, pskb, pstat);
	}
#endif // CLIENT_MODE
	else
	{
		priv->ext_stats.rx_data_drops++;
		DEBUG_ERR("RX DROP: Non supported mode in process_datafrme\n");
		return FAIL;
	}

	return SUCCESS;
}


/*
	Actually process RX management frame:

		Process management frame stored in "inputPfrinfo" or gotten from "list",
		only one of them is used to get Frame information.

			Note that:
			1. If frame information is gotten from "list", "inputPfrinfo" MUST be NULL.
			2. If frame information is gotten from "inputPfrinfo", "list" MUST be NULL
*/
#ifndef CONFIG_RTK_MESH
static
#endif
 void rtl8192cd_rx_mgntframe(struct rtl8192cd_priv *priv, struct list_head *list,
				struct rx_frinfo *inputPfrinfo)
{
	struct rx_frinfo *pfrinfo = NULL;

	// for SW LED
	if (priv->pshare->LED_cnt_mgn_pkt)
		priv->pshare->LED_rx_cnt++;

	/* Get RX frame info */
	if (list) {
		/* Indicate the frame information can be gotten from "list" */
		pfrinfo = list_entry(list, struct rx_frinfo, rx_list);
	}
	else {
		/* Indicate the frame information is stored in "inputPfrinfo" */
		pfrinfo = inputPfrinfo;
	}

	if (pfrinfo == NULL)
		goto out;

	mgt_handler(priv, pfrinfo);

out:
	return;
}


/*
	Actually process RX control frame:

		Process control frame stored in "inputPfrinfo" or gotten from "list",
		only one of them is used to get Frame information.

			Note that:
			1. If frame information is gotten from "list", "inputPfrinfo" MUST be NULL.
			2. If frame information is gotten from "inputPfrinfo", "list" MUST be NULL
*/
static void rtl8192cd_rx_ctrlframe(struct rtl8192cd_priv *priv, struct list_head *list,
				struct rx_frinfo *inputPfrinfo)
{
	struct rx_frinfo *pfrinfo = NULL;

	/* Get RX frame info */
	if (list) {
		/* Indicate the frame information can be gotten from "list" */
		pfrinfo = list_entry(list, struct rx_frinfo, rx_list);
	}
	else {
		/* Indicate the frame information is stored in "inputPfrinfo " */
		pfrinfo = inputPfrinfo;
	}

	if (pfrinfo == NULL)
		goto out;

	ctrl_handler(priv, pfrinfo);

out:
	return;
}


/*
	Actually process RX data frame:

		Process data frame stored in "inputPfrinfo" or gotten from "list",
		only one of them is used to get Frame information.

			Note that:
			1. If frame information is gotten from "list", "inputPfrinfo" MUST be NULL.
			2. If frame information is gotten from "inputPfrinfo", "list" MUST be NULL
*/
__MIPS16
__IRAM_IN_865X
 void rtl8192cd_rx_dataframe(struct rtl8192cd_priv *priv, struct list_head *list,
				struct rx_frinfo *inputPfrinfo)
{
	struct rx_frinfo *pfrinfo = NULL;
	unsigned char *pframe;

	/* ============== Do releted process for Packet RX ============== */
	// for SW LED
	priv->pshare->LED_rx_cnt++;

	// for Rx dynamic tasklet
	priv->pshare->rxInt_data_delta++;

	/* Get RX frame info */
	if (list) {
		/* Indicate the frame information can be gotten from "list" */
		pfrinfo = list_entry(list, struct rx_frinfo, rx_list);
	}
	else {
		/* Indicate the frame information is stored in "inputPfrinfo " */
		pfrinfo = inputPfrinfo;
	}

	if (pfrinfo == NULL) {
		printk("pfrinfo == NULL\n");
		goto out;
	}

	pframe = get_pframe(pfrinfo);

#ifdef WIFI_WMM
	if (is_qos_data(pframe)) {
		if ((OPMODE & WIFI_AP_STATE) && (QOS_ENABLE)) {
			if ((pfrinfo->tid == 7) || (pfrinfo->tid == 6)) {
				priv->pshare->phw->VO_pkt_count++;
			} else if ((pfrinfo->tid == 5) || (pfrinfo->tid == 4)) {
				priv->pshare->phw->VI_pkt_count++;
				if (priv->pshare->rf_ft_var.wifi_beq_iot)
					priv->pshare->phw->VI_rx_pkt_count++;
			} else if ((pfrinfo->tid == 2) || (pfrinfo->tid == 1)) {
				priv->pshare->phw->BK_pkt_count++;
			}
		}
	}
#endif

	// check power save state
#ifndef DRVMAC_LB
	if (OPMODE & WIFI_AP_STATE) {
		if (get_stainfo(priv, GetAddr2Ptr(pframe)) != NULL) {
			if (IS_BSSID(priv, GetAddr1Ptr(pframe))) {
				struct stat_info *pstat = get_stainfo(priv, pfrinfo->sa);
				if (pstat && (pstat->state & WIFI_ASOC_STATE) &&
						(GetPwrMgt(pframe) != ((pstat->state & WIFI_SLEEP_STATE ? 1 : 0))))
				  pwr_state(priv, pfrinfo);
			}
		}
	}
#endif

	/* ============== Start to process RX dataframe ============== */
#if defined(CONFIG_RTK_VOIP_QOS)|| defined(CONFIG_RTK_VLAN_WAN_TAG_SUPPORT)
#ifdef MBSSID
        if(IS_VAP_INTERFACE(priv))
                pfrinfo->pskb->srcPhyPort += (priv->vap_id+1);
#endif
#endif

#if defined(DRVMAC_LB) && defined(WIFI_WMM)
	if(priv->pmib->miscEntry.drvmac_lb /*&& priv->pmib->miscEntry.lb_tps*/) {
		if ((QOS_ENABLE) && (GetFrameSubType(get_pframe(pfrinfo)) == (BIT(7)|WIFI_DATA_NULL)))
			process_lb_qos_null(priv, pfrinfo);
		else if ((QOS_ENABLE) && (GetFrameSubType(get_pframe(pfrinfo)) == (WIFI_QOS_DATA)))
			process_lb_qos(priv, pfrinfo);
		else {
#if 0
			if (!QOS_ENABLE)
				printk("wifi qos not enabled, ");
			printk("cannot match loopback pkt pattern!!!\n");
			if (pfrinfo->pskb && pfrinfo->pskb->data) {
				unsigned int *p_skb_int = (unsigned int *)pfrinfo->pskb->data;
				printk("ERROR PKT===>> 0x%08x 0x%08x 0x%08x 0x%08x 0x%08x 0x%08x 0x%08x 0x%08x <<===\n",
					*p_skb_int, *(p_skb_int+1), *(p_skb_int+2), *(p_skb_int+3), *(p_skb_int+4), *(p_skb_int+5),
					*(p_skb_int+6), *(p_skb_int+7));
			}
#endif
			rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
		}
		goto out;
	}
#endif

	if (GetFrameSubType(get_pframe(pfrinfo)) == (BIT(7)|WIFI_DATA_NULL)) { //Intel 6205 IOT issue
		//printk("\n receive qos_null !!\n\n");
#if defined(WIFI_WMM) && defined(WMM_APSD)
		if((OPMODE & WIFI_AP_STATE) && (QOS_ENABLE)) {
			rtl8192cd_rx_handle_Spec_Null_Data(priv, pfrinfo); // for AR5007 IOT ISSUE
			process_qos_null(priv, pfrinfo);
		}
		else
#endif
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
		goto out;
	}

#if 0
#if defined(WIFI_WMM) && defined(WMM_APSD)
	if(
#ifdef CLIENT_MODE
		(OPMODE & WIFI_AP_STATE) &&
#endif
		(QOS_ENABLE) && (APSD_ENABLE) && (GetFrameSubType(get_pframe(pfrinfo)) == (BIT(7)|WIFI_DATA_NULL))) {
		rtl8192cd_rx_handle_Spec_Null_Data(priv, pfrinfo); // for AR5007 IOT ISSUE
		process_qos_null(priv, pfrinfo);
		goto out;
	}
#endif
#endif

	// for AR5007 IOT ISSUE
	if (GetFrameSubType(pframe) == WIFI_DATA_NULL) {
		rtl8192cd_rx_handle_Spec_Null_Data(priv, pfrinfo);
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
		goto out;
	}

#ifdef RX_SHORTCUT
	if (!priv->pmib->dot11OperationEntry.disable_rxsc &&
		!IS_MCAST(pfrinfo->da)
#if defined(WIFI_WMM) && defined(WMM_APSD)
		&& (!(
#ifdef CLIENT_MODE
			(OPMODE & WIFI_AP_STATE) &&
#endif
			(APSD_ENABLE) && (GetFrameSubType(get_pframe(pfrinfo)) == (WIFI_QOS_DATA)) && (GetPwrMgt(get_pframe(pfrinfo)))))
#endif
		) {
		if (rx_shortcut(priv, pfrinfo) >= 0) {
#if defined(__ECOS) && defined(_DEBUG_RTL8192CD_)
			priv->ext_stats.rx_cnt_sc++;
#endif
			goto out;
	}
	}
#endif

#if defined(__ECOS) && defined(_DEBUG_RTL8192CD_)
	priv->ext_stats.rx_cnt_nosc++; 
#endif
	pfrinfo = defrag_frame(priv, pfrinfo);

	if (pfrinfo == NULL)
		goto out;

	if (process_datafrme(priv, pfrinfo) == FAIL) {
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
	}

out:
	return;
}

#if !(defined(RTL8190_ISR_RX) && defined(RTL8190_DIRECT_RX))
void process_all_queues(struct rtl8192cd_priv *priv)
{
	struct list_head *list = NULL;

	// processing data frame first...
	while(1)
	{
		list = dequeue_frame(priv, &(priv->rx_datalist));

		if (list == NULL)
			break;

		rtl8192cd_rx_dataframe(priv, list, NULL);
	}

	// going to process management frame
	while(1)
	{
		list = dequeue_frame(priv, &(priv->rx_mgtlist));

		if (list == NULL)
			break;

		rtl8192cd_rx_mgntframe(priv, list, NULL);
	}

	while(1)
	{
		list = dequeue_frame(priv, &(priv->rx_ctrllist));

		if (list == NULL)
			break;

		rtl8192cd_rx_ctrlframe(priv, list, NULL);
	}

	if (!list_empty(&priv->wakeup_list))
		process_dzqueue(priv);
}
void flush_rx_queue(struct rtl8192cd_priv *priv)
{
	struct list_head *list = NULL;
	struct rx_frinfo *pfrinfo = NULL;

	while(1)
	{
		list = dequeue_frame(priv, &(priv->rx_datalist));

		if (list == NULL)
			break;

		pfrinfo = list_entry(list, struct rx_frinfo, rx_list);
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
	}
	
	while(1)
	{
		list = dequeue_frame(priv, &(priv->rx_mgtlist));

		if (list == NULL)
			break;

		pfrinfo = list_entry(list, struct rx_frinfo, rx_list);
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
	}
	
	while(1)
	{
		list = dequeue_frame(priv, &(priv->rx_ctrllist));

		if (list == NULL)
			break;

		pfrinfo = list_entry(list, struct rx_frinfo, rx_list);
		rtl_kfree_skb(priv, pfrinfo->pskb, _SKB_RX_);
	}
}


__IRAM_IN_865X
void rtl8192cd_rx_dsr(unsigned long task_priv)
{
	struct rtl8192cd_priv	*priv = (struct rtl8192cd_priv *)task_priv;
	unsigned long flags;
	unsigned long mask, mask_rx;
#ifdef MBSSID
	int i;
#endif

#ifndef __ASUS_DVD__
	extern int r3k_flush_dcache_range(int, int);
#endif

#ifdef __KERNEL__
	// disable rx interrupt in DSR
	SAVE_INT_AND_CLI(flags);
#ifdef CONFIG_RTL_88E_SUPPORT
	if (GET_CHIP_VER(priv)==VERSION_8188E) {
		if (priv->pshare->InterruptMask & HIMR_88E_ROK)
			RTL_W32(REG_88E_HIMR, priv->pshare->InterruptMask & ~HIMR_88E_ROK);
		if (priv->pshare->InterruptMaskExt & HIMRE_88E_RXFOVW)
			RTL_W32(REG_88E_HIMRE, priv->pshare->InterruptMaskExt & ~HIMRE_88E_RXFOVW);
	} else
#endif
	{
		mask = RTL_R32(HIMR);
		//mask_rx = mask & (HIMR_RXFOVW | HIMR_RDU | HIMR_ROK);
		mask_rx = mask & (HIMR_RXFOVW | HIMR_ROK);
		RTL_W32(HIMR, mask & ~mask_rx);
		//RTL_W32(HISR, mask_rx);
	}
#endif

	rtl8192cd_rx_isr(priv);

#ifdef __KERNEL__
	RESTORE_INT(flags);
#endif

	process_all_queues(priv);

#ifdef UNIVERSAL_REPEATER
	if (IS_DRV_OPEN(GET_VXD_PRIV(priv)))
		process_all_queues(GET_VXD_PRIV(priv));
#endif

#ifdef MBSSID
	if (GET_ROOT(priv)->pmib->miscEntry.vap_enable) {
		for (i=0; i<RTL8192CD_NUM_VWLAN; i++) {
			if (IS_DRV_OPEN(priv->pvap_priv[i]))
				process_all_queues(priv->pvap_priv[i]);
		}
	}
#endif

#ifdef __KERNEL__
#ifdef CONFIG_RTL_88E_SUPPORT
	if (GET_CHIP_VER(priv)==VERSION_8188E) {
		if (priv->pshare->InterruptMask & HIMR_88E_ROK)
			RTL_W32(REG_88E_HIMR, priv->pshare->InterruptMask);
		if (priv->pshare->InterruptMaskExt & HIMRE_88E_RXFOVW)
			RTL_W32(REG_88E_HIMRE, priv->pshare->InterruptMaskExt);
	} else
#endif
	{
		mask = RTL_R32(HIMR);
		RTL_W32(HIMR, mask | mask_rx);
	}
#endif

}
#endif // !(defined(RTL8190_ISR_RX) && defined(RTL8190_DIRECT_RX))


static void ctrl_handler(struct rtl8192cd_priv *priv, struct rx_frinfo *pfrinfo)
{
	struct sk_buff *pskbpoll, *pskb;
	unsigned char *pframe;
	struct stat_info *pstat;
	unsigned short aid;

// 2009.09.08
	unsigned long flags;

	DECLARE_TXINSN(txinsn);

	pframe  = get_pframe(pfrinfo);
	pskbpoll = get_pskb(pfrinfo);

	aid = GetAid(pframe);

	pstat = get_aidinfo(priv, aid);

	if (pstat == NULL)
		goto end_ctrl;

	// check if hardware address matches...
	if (memcmp(pstat->hwaddr, (void *)(pframe + 10), MACADDRLEN))
		goto end_ctrl;

SAVE_INT_AND_CLI(flags);

	// now dequeue from the pstat's dz_queue
	pskb = __skb_dequeue(&pstat->dz_queue);

RESTORE_INT(flags);


	if (pskb == NULL)
		goto end_ctrl;

	txinsn.q_num   = BE_QUEUE; //using low queue for data queue
	txinsn.fr_type = _SKB_FRAME_TYPE_;
	txinsn.pframe  = pskb;
	txinsn.phdr	   = (UINT8 *)get_wlanllchdr_from_poll(priv);
	pskb->cb[1] = 0;

	if (pskb->len > priv->pmib->dot11OperationEntry.dot11RTSThreshold)
		txinsn.retry = priv->pmib->dot11OperationEntry.dot11LongRetryLimit;
	else
		txinsn.retry = priv->pmib->dot11OperationEntry.dot11ShortRetryLimit;

	if (txinsn.phdr == NULL) {
		DEBUG_ERR("Can't alloc wlan header!\n");
		goto xmit_skb_fail;
	}

	memset((void *)txinsn.phdr, 0, sizeof(struct wlanllc_hdr));

	SetFrDs(txinsn.phdr);
#ifdef WIFI_WMM
	if (pstat && (QOS_ENABLE) && (pstat->QosEnabled))
		SetFrameSubType(txinsn.phdr, WIFI_QOS_DATA);
	else
#endif
	SetFrameSubType(txinsn.phdr, WIFI_DATA);

	if (skb_queue_len(&pstat->dz_queue))
		SetMData(txinsn.phdr);

#ifdef A4_STA
	if ((pstat->state & WIFI_A4_STA) && IS_MCAST(pskb->data)) {
		txinsn.pstat = pstat;
		SetToDs(txinsn.phdr);
	}
#endif

	if (rtl8192cd_wlantx(priv, &txinsn) == CONGESTED)
	{

xmit_skb_fail:

		priv->ext_stats.tx_drops++;
		DEBUG_WARN("TX DROP: Congested!\n");
		if (txinsn.phdr)
			release_wlanllchdr_to_poll(priv, txinsn.phdr);
		if (pskb)
			rtl_kfree_skb(priv, pskb, _SKB_TX_);
	}

end_ctrl:

	if (pskbpoll) {
		rtl_kfree_skb(priv, pskbpoll, _SKB_RX_);
	}

	return;
}


/*
typedef struct tx_sts_struct
{
	// DW 1
	UINT8	TxRateid;
	UINT8	TxRate;
} tx_sts;

typedef struct tag_Tx_Status_Feedback
{
	// For endian transfer --> for driver
	// DW 0
	UINT16	Length;					// Command packet length
	UINT8 	Reserve1;
	UINT8 	Element_ID;			// Command packet type

	tx_sts    Tx_Sts[NUM_STAT];
} CMPK_TX_STATUS_T;
*/

#ifdef RX_BUFFER_GATHER
static struct sk_buff *get_next_skb(struct rtl8192cd_priv *priv, int remove, int *is_last)
{
	struct rx_frinfo *pfrinfo = NULL;
	struct sk_buff *pskb = NULL;
	struct list_head *phead, *plist;
	unsigned long flags;

	SAVE_INT_AND_CLI(flags);

	phead = &priv->pshare->gather_list;
	plist = phead->next;

	if (plist != phead) {
		pfrinfo = list_entry(plist, struct rx_frinfo, rx_list);
		pskb = get_pskb(pfrinfo);
		if (pskb) {
			pskb->tail = pskb->data + pfrinfo->pktlen;
			pskb->len = pfrinfo->pktlen;
			pskb->dev = priv->dev;
			if (remove)
				list_del_init(plist);
		}
	}

	if (is_last && pskb && pfrinfo->gather_flag == GATHER_LAST)
		*is_last = 1;

	RESTORE_INT(flags);
	return pskb;
}

static struct sk_buff *shift_padding_len(struct rtl8192cd_priv *priv, struct sk_buff *skb, int len, int *is_last)
{
	struct sk_buff *nskb= skb ;

	if (skb->len < len) {
		if (*is_last)
			return NULL;

		nskb = get_next_skb(priv, 1, is_last);
		if (nskb)
			skb_pull(nskb, len - skb->len);
		else
			DEBUG_ERR("Shift len error (%d, %d)!\n", skb->len, len);
	}
	else
		skb_pull(nskb, len);

	return nskb;
}

static int get_subframe_len(struct rtl8192cd_priv *priv, struct sk_buff *skb, int is_last)
{
	u8 sub_len[2];
	struct sk_buff *nskb;
	int offset;
	u16 u16_len;

	if (skb->len < WLAN_ETHHDR_LEN) {
		if (is_last)
			return 0;

		if (skb->len == WLAN_ETHHDR_LEN -1) {
			sub_len[0] = skb->data[MACADDRLEN*2];
			offset = 1;
		}
		else
			offset = WLAN_ETHHDR_LEN -2 - skb->len;

		nskb = get_next_skb(priv, 0, NULL);
		if (nskb == NULL)
			return -1;

		if (offset == 1)
			sub_len[1] = nskb->data[0];
		else
			memcpy(sub_len, nskb->data + offset, 2);
	}
	else
		memcpy(sub_len, &skb->data[MACADDRLEN*2], 2);

	u16_len = ntohs(*((u16 *)sub_len));
	return ((int)u16_len);
}

static struct sk_buff *get_subframe(struct rtl8192cd_priv *priv, struct sk_buff *skb, struct sk_buff **orgskb, int len, int *is_last)
{
	struct sk_buff *nextskb=NULL, *joinskb;
	int offset, copy_len;

	if (skb->len < len+WLAN_ETHHDR_LEN) {
		int rest_len = len + WLAN_ETHHDR_LEN - skb->len;

		if (*is_last)
			return NULL;

		joinskb = dev_alloc_skb(len + WLAN_ETHHDR_LEN);
		if (joinskb == NULL) {
			DEBUG_ERR("dev_alloc_skb() failed!\n");
			return NULL;
		}
		memcpy(joinskb->data, skb->data, skb->len);
		offset = skb->len;

		do {
			if (nextskb)
				rtl_kfree_skb(priv, nextskb, _SKB_RX_);

			nextskb = get_next_skb(priv, 1, is_last);
			if (nextskb == NULL) {
				dev_kfree_skb_any(joinskb);
				return NULL;
			}

			if (nextskb->len < rest_len && *is_last) {
				dev_kfree_skb_any(joinskb);
				rtl_kfree_skb(priv, nextskb, _SKB_RX_);
				return NULL;
			}
			if (nextskb->len < rest_len)
				copy_len = nextskb->len;
			else
				copy_len = rest_len;

			memcpy(joinskb->data+offset, nextskb->data, copy_len);
			rest_len -= copy_len;
			offset += copy_len;
			skb_pull(nextskb, copy_len);
		}while (rest_len > 0);
		rtl_kfree_skb(priv, *orgskb, _SKB_RX_);
		*orgskb = nextskb;
		skb = joinskb;
	}
	else
		skb_pull(*orgskb, len+WLAN_ETHHDR_LEN);

	return skb;
}
#endif /* RX_BUFFER_GATHER */

#ifdef CONFIG_RTL_KERNEL_MIPS16_WLAN
__NOMIPS16
#endif
static void process_amsdu(struct rtl8192cd_priv *priv, struct stat_info *pstat, struct rx_frinfo *pfrinfo)
{
	unsigned char	*pframe, *da;
	struct stat_info	*dst_pstat = NULL;
	struct sk_buff	*pskb  = NULL, *pnewskb = NULL;
	unsigned char	*next_head;
	int				rest, agg_pkt_num=0, i, privacy;
	unsigned int	subfr_len, padding;
	const unsigned char rfc1042_ip_header[8]={0xaa,0xaa,0x03,00,00,00,0x08,0x00};
#ifdef RX_BUFFER_GATHER
	struct sk_buff *nskb;
	int rx_gather = 0;
	int is_last = 0;
#endif

	pframe = get_pframe(pfrinfo);
	pskb = get_pskb(pfrinfo);

	rest = pfrinfo->pktlen - pfrinfo->hdr_len;
	next_head = pframe + pfrinfo->hdr_len;

#ifdef RX_BUFFER_GATHER
	if (pfrinfo->gather_flag == GATHER_FIRST) {
		skb_pull(pskb, pfrinfo->hdr_len);
		rest = pfrinfo->gather_len - pfrinfo->hdr_len;
		rx_gather = 1;
	}
#endif

	if (GetPrivacy(pframe)) {
#ifdef WDS
		if (pfrinfo->to_fr_ds==3)
			privacy = priv->pmib->dot11WdsInfo.wdsPrivacy;
		else
#endif
			privacy = get_sta_encrypt_algthm(priv, pstat);
		if ((privacy == _CCMP_PRIVACY_) || (privacy == _TKIP_PRIVACY_)) {
			rest -= 8;
			next_head += 8;
#ifdef RX_BUFFER_GATHER
			pskb->data += 8;
                        pskb->len -= 8;
#endif

		}
		else {	// WEP
			rest -= 4;
			next_head += 4;
#ifdef RX_BUFFER_GATHER
			pskb->data += 4;
                        pskb->len -= 4;
#endif

		}
	}

	while (rest > WLAN_ETHHDR_LEN) {
		pnewskb = skb_clone(pskb, GFP_ATOMIC);
		if (pnewskb) {
			pnewskb->data = next_head;
#ifdef RX_BUFFER_GATHER
			if (rx_gather) {
				subfr_len = get_subframe_len(priv, pnewskb, is_last);
				if (subfr_len <= 0) {
					DEBUG_ERR("invalid subfr_len=%d, discard AMSDU!\n", subfr_len);
					dev_kfree_skb_any(pnewskb);
					break;
				}
				nskb = get_subframe(priv, pnewskb, &pskb, subfr_len, &is_last);
				if (nskb == NULL) {
					DEBUG_ERR("get_subframe() failed, discard AMSDU!\n");
					dev_kfree_skb_any(pnewskb);
					priv->ext_stats.rx_data_drops++;
					break;
				}
				if (nskb != pnewskb) {
					nskb->dev = pnewskb->dev;
					dev_kfree_skb_any(pnewskb);
					pnewskb = nskb;
				}
			}
			else
#endif
			subfr_len = (*(next_head + MACADDRLEN*2) << 8) + (*(next_head + MACADDRLEN*2 + 1));

			pnewskb->len = WLAN_ETHHDR_LEN + subfr_len;
			pnewskb->tail = pnewskb->data + pnewskb->len;

			if(pnewskb->tail > pnewskb->end) {
				rtl_kfree_skb(priv, pnewskb, _SKB_RX_);
				priv->ext_stats.rx_data_drops++;
				DEBUG_ERR("RX DROP: sub-frame length too large!\n");
				break;
			}

			if (!memcmp(rfc1042_ip_header, pnewskb->data+WLAN_ETHHDR_LEN, 8)) {
				for (i=0; i<MACADDRLEN*2; i++)
					pnewskb->data[19-i] = pnewskb->data[11-i];
				pnewskb->data += 8;
				pnewskb->len -= 8;
			}
			else
				strip_amsdu_llc(priv, pnewskb, pstat);
			agg_pkt_num++;

			if (OPMODE & WIFI_AP_STATE)
			{
				da = pnewskb->data;
				dst_pstat = get_stainfo(priv, da);
				if ((dst_pstat == NULL) || (!(dst_pstat->state & WIFI_ASOC_STATE)))
					rtl_netif_rx(priv, pnewskb, pstat);
				else
				{
					if (priv->pmib->dot11OperationEntry.block_relay == 1) {
						priv->ext_stats.rx_data_drops++;
						DEBUG_ERR("RX DROP: Relay unicast packet is blocked!\n");
#ifdef RX_SHORTCUT
						if (dst_pstat->rx_payload_offset > 0) // shortcut data saved, clear it
							dst_pstat->rx_payload_offset = 0;
#endif
						rtl_kfree_skb(priv, pnewskb, _SKB_RX_);
					}
					else if (priv->pmib->dot11OperationEntry.block_relay == 2) {
						DEBUG_INFO("Relay unicast packet is blocked! Indicate to bridge.\n");
						rtl_netif_rx(priv, pnewskb, pstat);
					}
					else {
#ifdef ENABLE_RTL_SKB_STATS
						rtl_atomic_dec(&priv->rtl_rx_skb_cnt);
#endif
#ifdef GBWC
						if (GBWC_forward_check(priv, pnewskb, pstat)) {
							// packet is queued, nothing to do
						}
						else
#endif
						{
#ifdef TX_SCATTER
							pnewskb->list_num = 0;
#endif
#ifdef CONFIG_RTK_MESH
							if (rtl8192cd_start_xmit(pnewskb, isMeshPoint(dst_pstat) ? priv->mesh_dev: priv->dev))
#else
						if (rtl8192cd_start_xmit(pnewskb, priv->dev))
#endif
							rtl_kfree_skb(priv, pnewskb, _SKB_RX_);
						}
					}
				}
			}
#ifdef CLIENT_MODE
			else if (OPMODE & (WIFI_STATION_STATE | WIFI_ADHOC_STATE))
			{
#ifdef RTK_BR_EXT
				if(nat25_handle_frame(priv, pnewskb) == -1) {
					priv->ext_stats.rx_data_drops++;
					DEBUG_ERR("RX DROP: nat25_handle_frame fail!\n");
					rtl_kfree_skb(priv, pnewskb, _SKB_RX_);
				}
#endif
				rtl_netif_rx(priv, pnewskb, pstat);
			}
#endif

			padding = 4 - ((WLAN_ETHHDR_LEN + subfr_len) % 4);
			if (padding == 4)
				padding = 0;
			rest -= (WLAN_ETHHDR_LEN + subfr_len + padding);

#ifdef RX_BUFFER_GATHER
			if (rx_gather) {
				if ((rest <= WLAN_ETHHDR_LEN) && is_last)
					break;

				nskb = shift_padding_len(priv, pskb, padding, &is_last);
				if (nskb == NULL) {
					DEBUG_ERR("shift AMSDU padding len error!\n");
					break;
				}
				if (nskb != pskb) {
					rtl_kfree_skb(priv, pskb, _SKB_RX_);
					pskb = nskb;
				}
				next_head = pskb->data;
			}
			else
#endif
			next_head += (WLAN_ETHHDR_LEN + subfr_len + padding);
		}
		else {
			// Can't get new skb header, drop this packet
			break;
		}
	}

	// clear saved shortcut data
#ifdef RX_SHORTCUT
	if (pstat->rx_payload_offset)
		pstat->rx_payload_offset = 0;
#endif

#ifdef _DEBUG_RTL8192CD_
	switch (agg_pkt_num) {
	case 0:
		pstat->rx_amsdu_err++;
		break;
	case 1:
		pstat->rx_amsdu_1pkt++;
		break;
	case 2:
		pstat->rx_amsdu_2pkt++;
		break;
	case 3:
		pstat->rx_amsdu_3pkt++;
		break;
	case 4:
		pstat->rx_amsdu_4pkt++;
		break;
	case 5:
		pstat->rx_amsdu_5pkt++;
		break;
	default:
		pstat->rx_amsdu_gt5pkt++;
		break;
	}
#endif

	rtl_kfree_skb(priv, pskb, _SKB_RX_);
}

