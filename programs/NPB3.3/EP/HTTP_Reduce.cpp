#include <iostream>
#include <cstdio>
#include <cstdlib>
#include <cstring>
#include <string>
#include <unistd.h>
#include <random>
#include <climits>
#include <cfloat>

#include "utils.h"

#define BUF_LEN 1024 /* バッファのサイズ */
#define TWAIT 100000 // 再接続の待ち時間（マイクロ秒）

MYEXTERN_C void HTTP_Reduce(int YOUR_ID, int reduce_ID, int numprocs, void *send_hiki_DATA_P, void *recv_hiki_DATA_P, int DATA_LEN, const char *DATA_TYPE, const char *op, int isolation)
{
	MYTimer timer("Reduce",YOUR_ID); // ID,DATA用タイマー
	MYSocket sock(80,DESTINATION);

	int data_length = 0;
	std::string body;
	std::string filename;
	bool flag = false;
	char *send_DATA_P = (char *)send_hiki_DATA_P;
	char *recv_DATA_P = (char *)recv_hiki_DATA_P;

	// recv_DATA_Pの初期化
	// MPI_MAX: その型の最小値
	// MPI_MIN: その型の最大値
	// MPI_SUM: 0
	// mpi型はUNSIGNDがない限り符号つき型
	if(reduce_ID==YOUR_ID){
		timer.start();
		if(!strcmp(DATA_TYPE, "MPI_CHAR"))
			logprintf("MPI_CHAR is not impremented");
		else if(!strcmp(DATA_TYPE, "MPI_SHORT")){
			if(!strcmp(op,"MPI_MAX"))
				for(int i=0;i<DATA_LEN;i++)
					((short*)recv_DATA_P)[i]=-1*SHRT_MAX;

			else if(!strcmp(op,"MPI_MIN"))
				for(int i=0;i<DATA_LEN;i++)
					((short*)recv_DATA_P)[i]=SHRT_MAX;

			else if(!strcmp(op,"MPI_SUM"))
				for(int i=0;i<DATA_LEN;i++)
					((short*)recv_DATA_P)[i]=0;

		}else if(!strcmp(DATA_TYPE, "MPI_INT")){
			if(!strcmp(op,"MPI_MAX"))
				for(int i=0;i<DATA_LEN;i++)
					((int*)recv_DATA_P)[i]=-1*INT_MAX;

			else if(!strcmp(op,"MPI_MIN"))
				for(int i=0;i<DATA_LEN;i++)
					((int*)recv_DATA_P)[i]=INT_MAX;

			else if(!strcmp(op,"MPI_SUM"))
				for(int i=0;i<DATA_LEN;i++)
					((int*)recv_DATA_P)[i]=0;

		}else if(!strcmp(DATA_TYPE, "MPI_LONG")){
			if(!strcmp(op,"MPI_MAX"))
				for(int i=0;i<DATA_LEN;i++)
					((long*)recv_DATA_P)[i]=-1*LONG_MAX;

			else if(!strcmp(op,"MPI_MIN"))
				for(int i=0;i<DATA_LEN;i++)
					((long*)recv_DATA_P)[i]=LONG_MAX;

			else if(!strcmp(op,"MPI_SUM"))
				for(int i=0;i<DATA_LEN;i++)
					((long*)recv_DATA_P)[i]=0;

		}else if(!strcmp(DATA_TYPE, "MPI_FLOAT")){
			if(!strcmp(op,"MPI_MAX"))
				for(int i=0;i<DATA_LEN;i++)
					((float*)recv_DATA_P)[i]=-1*FLT_MAX;

			else if(!strcmp(op,"MPI_MIN"))
				for(int i=0;i<DATA_LEN;i++)
					((float*)recv_DATA_P)[i]=FLT_MAX;

			else if(!strcmp(op,"MPI_SUM"))
				for(int i=0;i<DATA_LEN;i++)
					((float*)recv_DATA_P)[i]=0.0;

		}else if(!strcmp(DATA_TYPE, "MPI_DOUBLE")){
			if(!strcmp(op,"MPI_MAX"))
				for(int i=0;i<DATA_LEN;i++)
					((double*)recv_DATA_P)[i]=-1*DBL_MAX;

			else if(!strcmp(op,"MPI_MIN"))
				for(int i=0;i<DATA_LEN;i++)
					((double*)recv_DATA_P)[i]=DBL_MAX;

			else if(!strcmp(op,"MPI_SUM"))
				for(int i=0;i<DATA_LEN;i++)
					((double*)recv_DATA_P)[i]=0.0;
		}
		// recv_DATA_Pの初期化　ここまで
		timer.stop();
		timer.print_diff("Init","recvDATAP");
	}

	std::mt19937 mt{std::random_device{}()};
	std::uniform_int_distribution<int> dist(0, 100);
	int rand = 0;

	const char* httppath[]={
		"/VC/reduce/MPI_Reduce_ID.php",
		"/VC/reduce/MPI_Reduce_DATA.php",
		"/VC/reduce/MPI_Reduce_recDATA.php",
		"/VC/reduce/MPI_Reduce_FLAG.php"
	};


	/* 各種パラメータ */
	char toSendText[BUF_LEN];
	char buf[BUF_LEN + 2];  	//buf[BUF_LEN]='\n',buf[BUF_LEN+1]='\0'
	int read_size = 0;

	bool res_ok;

	/*************************************************************************/


	/* ソケット生成 */
	sock.mysock();

	/* 接続 */
	timer.start();
	sock.myconn();
	timer.stop();
	timer.print_diff("ID","connect");

	logprintf("\n%s Connect OK\n", httppath[ID]);

	/* HTTP プロトコル生成 & サーバに送信 */
	body = "reduce_ID=" + std::to_string(reduce_ID) + "&NUM_PROCS=" + std::to_string(numprocs) + "&YOUR_ID=" + std::to_string(YOUR_ID) + "&ISOLATION=" + std::to_string(isolation);

	logprintf("\nSending a request...");

	timer.start();
	// headerの送信
	normal_header(&sock,httppath[ID],YOUR_ID,(int)body.length());
	// Bodyの送信
	sock.mysend(body.c_str(), (int)body.length());

	//rand = dist(mt) * 1000;
	//usleep(50000 + rand);

	timer.date();
	timer.stop();
	logprintf("OK\n\n");
	timer.print_diff("ID","sendfunction");
	timer.print_date("ID","start");

	/* レスポンスの受信 & 解析*/
	logprintf("\nReceiving a response of ID ...");
	
	recv_message_not_no(sock, timer, YOUR_ID, BUF_LEN); // timerは関数内で実行

	/* socket close */
	sock.myclose();

	timer.print_diff("ID","recvfunction");
	timer.print_date("ID","end");

	timer.print_and_clear_total("ID");

	/*************************************************************************/

	filename = std::to_string(isolation) + "_ReduceID_" + std::to_string(reduce_ID) + "_" + std::to_string(YOUR_ID) + ".txt";

	/* ソケット生成 */
	sock.mysock();

	/* 接続 */
	timer.start();
	sock.myconn();
	timer.stop();
	timer.print_diff("DATA","connect");

	logprintf("\n%s Connect OK\n", httppath[DATA]);

	/* HTTP プロトコル生成 & サーバに送信 */
	logprintf("\nSending a request...");
	timer.start();
	// HTTP Body部の文字数を計算
	for(int i=0;i<DATA_LEN;i++){
		memset(toSendText, 0, sizeof(toSendText));
		if (!strcmp(DATA_TYPE, "MPI_CHAR"))
			sprintf(toSendText, "%c\n", send_DATA_P[i]);
		else if (!strcmp(DATA_TYPE, "MPI_SHORT"))
			sprintf(toSendText, "%d\n", ((short*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_INT"))
			sprintf(toSendText, "%d\n", ((int*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_LONG"))
			sprintf(toSendText, "%ld\n", ((long*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_FLOAT"))
			sprintf(toSendText, "%f\n", ((float*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_DOUBLE"))
			sprintf(toSendText, "%lf\n", ((double*)send_DATA_P)[i]);
		data_length = data_length + strlen(toSendText);
	}

	data_length = data_length + text_end_len(filename.c_str());

	// headerの送信
	file_header(&sock,httppath[DATA],YOUR_ID,data_length,filename.c_str());
	// Bodyの送信
	for(int i=0;i<DATA_LEN;i++){
		memset(toSendText, 0, sizeof(toSendText));
		if (!strcmp(DATA_TYPE, "MPI_CHAR"))
			sprintf(toSendText, "%c\n", send_DATA_P[i]);
		else if (!strcmp(DATA_TYPE, "MPI_SHORT"))
			sprintf(toSendText, "%d\n", ((short*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_INT"))
			sprintf(toSendText, "%d\n", ((int*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_LONG"))
			sprintf(toSendText, "%ld\n", ((long*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_FLOAT"))
			sprintf(toSendText, "%f\n", ((float*)send_DATA_P)[i]);
		else if (!strcmp(DATA_TYPE, "MPI_DOUBLE"))
			sprintf(toSendText, "%lf\n", ((double*)send_DATA_P)[i]);
		sock.mysend(toSendText, strlen(toSendText));
	}

	text_end(&sock);

	//rand = dist(mt) * 1000;
	//usleep(50000 + rand);

	timer.date();
	timer.stop();
	logprintf("OK\n\n");
	logprintf("file size is %d\n", data_length);
	timer.print_diff("DATA","sendfunction");
	timer.print_date("DATA","start");

	/* レスポンスの受信 & 解析*/
	logprintf("\nReceiving a response of DATA...");

	recv_message_not_no(sock, timer, YOUR_ID, BUF_LEN); // timerは関数内で実行

	/* socket close */
	sock.myclose();

	timer.print_diff("DATA","recvfunction");
	timer.print_date("DATA","end");

	timer.print_and_clear_total("DATA");

	/*************************************************************************/

	body = "DATA_TITTLE=" + filename + "&numprocs=" + std::to_string(numprocs);
	
	res_ok=false;
	while (1)
	{
		if(reduce_ID != YOUR_ID) break; // reduce_IDで指定されたワーカが受け取る
		timer.count_up(); // ループ回数をカウント
		/* ソケット生成 */
		sock.mysock();

		/* 接続 */
		timer.start();
		sock.myconn();
		timer.stop();
		timer.print_diff("recDATA","connect");

		logprintf("\n%s Connect OK\n", httppath[recDATA]);

		/* HTTP プロトコル生成 & サーバに送信 */
		logprintf("\nSending a request...");

		timer.start();
		// headerの送信 
		normal_header(&sock,httppath[recDATA],YOUR_ID,(int)body.length());
		// Bodyの送信
		sock.mysend(body.c_str(), body.length());

		//rand = dist(mt) * 1000;
		//usleep(50000 + rand);

		timer.date();
		timer.stop();
		logprintf("OK\n");
		timer.print_diff("recDATA","sendfunction");
		timer.print_date("recDATA","start");

		/* レスポンスを受信 & 解析 */
		logprintf("\nReceiving a response of recDATA ...");

	
		int array_position=0; // 読み取った値を格納する配列のインデックス
		int data_id=0; // データを送ったワーカのid
		int recv_cnt=0; // 指定されたワーカから受け取ったデータのカウンタ

		timer.start();
		_RECV_FILE(
			// v: 読み取った値を適切な型に変換したもの
			// p: 読み取った値を格納する場所
			if(!strcmp(DATA_TYPE, "MPI_CHAR"))
				logprintf("MPI_CHAR is not impremented");

			else if(!strcmp(DATA_TYPE, "MPI_SHORT")){
				short v=(short)atoi(buf_read);
				short *p=(short*)recv_DATA_P+array_position;
				if(!strcmp(op,"MPI_MAX") && v>*p)
					*p=v;
				else if(!strcmp(op,"MPI_MIN") && v<*p)
					*p=v;
				else if(!strcmp(op,"MPI_SUM"))
					*p+=v;

			}else if(!strcmp(DATA_TYPE, "MPI_INT")){
				int v=(int)atoi(buf_read);
				int *p=(int*)recv_DATA_P+array_position;
				if(!strcmp(op,"MPI_MAX") && v>*p)
					*p=v;
				else if(!strcmp(op,"MPI_MIN") && v<*p)
					*p=v;
				else if(!strcmp(op,"MPI_SUM"))
					*p+=v;

			}else if(!strcmp(DATA_TYPE, "MPI_LONG")){
				long v=(long)atol(buf_read);
				long *p=(long*)recv_DATA_P+array_position;
				if(!strcmp(op,"MPI_MAX") && v>*p)
					*p=v;
				else if(!strcmp(op,"MPI_MIN") && v<*p)
					*p=v;
				else if(!strcmp(op,"MPI_SUM"))
					*p+=v;

			}else if(!strcmp(DATA_TYPE, "MPI_FLOAT")){
				float v=(float)atof(buf_read);
				float *p=(float*)recv_DATA_P+array_position;
				if(!strcmp(op,"MPI_MAX") && v>*p)
					*p=v;
				else if(!strcmp(op,"MPI_MIN") && v<*p)
					*p=v;
				else if(!strcmp(op,"MPI_SUM"))
					*p+=v;

			}else if(!strcmp(DATA_TYPE, "MPI_DOUBLE")){
				double v=(double)atof(buf_read);
				double *p=(double*)recv_DATA_P+array_position;
				if(!strcmp(op,"MPI_MAX") && v>*p)
					*p=v;
				else if(!strcmp(op,"MPI_MIN") && v<*p)
					*p=v;
				else if(!strcmp(op,"MPI_SUM"))
					*p+=v;
			}
			// 値の格納ここまで

			array_position++;
			if(array_position==DATA_LEN){
				array_position=0;
			}
		)
		timer.stop();

		timer.print_diff("recDATA","recvfunction");
		timer.print_date("recDATA","end");
		/* socket close */
		sock.myclose();

		if(res_ok==true){
			logprintf("Received data complete\n");
			timer.print_and_clear_lap("recDATA");
			break;
		}

		timer.start();
		usleep(TWAIT);
		timer.stop();
		timer.print_and_clear_lap("recDATA");
	}
	timer.print_and_clear_total("recDATA");
	/*************************************************************************/

	body = "DATA_TITTLE=" + filename + "&YOUR_ID=" + std::to_string(YOUR_ID);
	
	res_ok=false;
	while (1)
	{
		if(reduce_ID == YOUR_ID) break; // reduce_IDで指定されたワーカはフラグ確認しない
		timer.count_up(); // ループ回数をカウント

		/* ソケット生成 */
		sock.mysock();

		/* 接続 */
		timer.start();
		sock.myconn();
		timer.stop();
		timer.print_diff("FLAG","connect");

		logprintf("\n%s Connect OK\n", httppath[FLAG]);

		/* HTTP プロトコル生成 & サーバに送信 */
		logprintf("\nSending a request...");

		timer.start();
		// headerの送信
		normal_header(&sock,httppath[FLAG],YOUR_ID,(int)body.length());
		// Bodyの送信
		sock.mysend(body.c_str(), body.length());

		//rand = dist(mt) * 1000;
		//usleep(50000 + rand);

		timer.date();
		timer.stop();
		logprintf("OK\n");
		timer.print_diff("FLAG","sendfunction");
		timer.print_date("FLAG","start");

		/* レスポンスの受信 & 解析 */
		logprintf("\nReceiving a response of FLAG...");

		res_ok=recv_message_ok(sock, timer, YOUR_ID, BUF_LEN); // timerは関数内で実行
		timer.print_diff("FLAG","recvfunction");
		timer.print_date("FLAG","end");

		/* socket close */
		sock.myclose();

		if (res_ok==true){
			logprintf("MPI_Reducev completed\n\n");
			timer.print_and_clear_lap("FLAG");
			break;
		}
		timer.start();
		usleep(TWAIT);
		timer.stop();
		timer.print_and_clear_lap("FLAG");
	}
	timer.print_and_clear_total("FLAG");
}

// fortran実行用 コンパイル時に -D MYFORTRAN で有効化
// fortranで呼び出すために
// 1. 関数名の後はアンダーバー, 2. 引数はすべてポインタ, 3. 変数名, 引数の大文字小文字は区別されない
#ifdef MYFORTRAN
extern "C" void http_reduce_(int *YOUR_ID, int *reduce_ID, int *numprocs, void *send_hiki_DATA_P, void *recv_hiki_DATA_P, int *DATA_LEN, int *DATA_TYPE, int *op, int *isolation)
{
	const char* type_c=ftype_to_ctype(*DATA_TYPE);
	const char* op_c=fop_to_cop(*op);
	
	HTTP_Reduce(*YOUR_ID, *reduce_ID, *numprocs, send_hiki_DATA_P, recv_hiki_DATA_P, *DATA_LEN, type_c, op_c, *isolation);
}
#endif
