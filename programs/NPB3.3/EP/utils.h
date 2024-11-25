#ifndef HTTP_UTILS_HPP
#define HTTP_UTILS_HPP

#include <cassert>
#include "destination.h"

// MYSocket.cpp
#include "MYSocket.hpp"

// MYTimer.cpp
#include "MYTimer.hpp"

#include "MYLog.h"

#include "MYMessage.hpp"

#ifdef MYFORTRAN
// Exchange_type_fortrun_to_c.cpp
const char *ftype_to_ctype(int type);
const char *fop_to_cop(int op);
#endif

// cのプログラムで使うときは, gccの -D MYCLANG オプションで切り替える
#ifdef MYCLANG
#define MYEXTERN_C extern "C"
#else
#define MYEXTERN_C
#endif

// phpファイルの指定に使用
#define ID 0
#define DATA 1
#define recDATA 2
#define FLAG 3

// 受信コード
#define _RECV_FILE(CODE) \
do{\
bool separated=false; /* 値が分断されているか*/\
char buf_read[25]; /* 値1つ分の文字列 */\
int buf_read_p=0; /* buf_readの読み取り位置 */\
;\
memset(buf, 0, sizeof(buf));\
recv_response_header(sock,timer,buf,BUF_LEN,YOUR_ID);\
int content_len=get_content_length(buf, YOUR_ID);\
char* body_start=strstr(buf,"\r\n\r\n")+4;\
if(strncmp(body_start,"OK\n",3)!=0){\
	logprintf("%s\n",body_start);\
	break;\
}\
res_ok=true;\
strcpy(buf,body_start+3); /*ヘッダーとOK\nの削除*/\
int offset=strlen(buf);\
int total_len=offset+3;\
sprintf(&buf[offset],"\n"); /*門番として\n\0を末尾に加える*/\
while (1){\
	if(total_len<content_len){\
		read_size = sock.myrecv(&buf[offset], BUF_LEN-offset); /* レスポンスをbufに読む, 成功 受信バイト数, 失敗 -1 */\
		sprintf(&buf[read_size+offset],"\n"); /*門番として\n\0を末尾に加える*/\
		total_len+=read_size;\
	}else if(strlen(buf)==0) break;\
	offset=0; /*以後0*/\
	/*show_buf(buf,sizeof(buf),YOUR_ID);*/\
	int buf_p=0; /* bufの読み取り位置 */\
	while(1){\
		/* 値が分断されていなければ初期化 */\
		if(separated==false){\
			memset(buf_read, 0, sizeof(buf_read));\
			buf_read_p=0;\
		}\
		/* bufからbuf_readに値1つ分の文字列を読み込む */\
		while(buf[buf_p] != '\n'){\
			buf_read[buf_read_p++] = buf[buf_p++];\
		}\
		buf_p++;\
		/* \nの次が\0ならば、値が分断されている */\
		if(buf[buf_p]=='\0'){\
			separated=true;\
			break;\
		}else{\
			separated=false;\
\
			/* ここに1つの値を適当な型に変換し配列に代入する動作 */\
			CODE\
\
			/* \nの次が\nならbufの値をすべて読み込んだ */\
			if(buf[buf_p]=='\n') break;\
		}\
	}\
	memset(buf, 0, sizeof(buf));\
}\
}while(0);

#define _RECV_DEFO(TIMER) \
do{\
	while (1){\
		memset(buf, 0, sizeof(buf));\
		read_size = sock.myrecv(buf, BUF_LEN);\
		buf[BUF_LEN] = '\0';\
		if(read_size>0){\
			TIMER.date();\
			show_buf(buf,sizeof(buf),YOUR_ID);\
			remove_response_header(buf,sizeof(buf));\
			TIMER.stop();\
			if (!strncmp(buf, "OK\n",3)){\
				logprintf("OK\n");\
				res_ok=true;\
			}\
			logprintf("%s",buf);\
			break;\
		}\
	}\
}while(0);

#endif
