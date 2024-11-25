#include <arpa/inet.h>
#include <iostream>
#include <netinet/in.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <string>
#include <sys/socket.h>
#include <sys/types.h>
#include <sys/time.h>
#include <unistd.h>
#include <random>
#include <time.h>

#define BUF_LEN 1024 /* バッファのサイズ */

void barrier(int YOUR_ID, int numprocs, int isolation)
{
	/* 時間計測≠cpu時間 */
	struct timeval tv1,tv2,tv3,tv4,tv5,tv6,tv7,tv8;
	struct tm *time1,*time2,*time3,*time4,*time5,*time6,*time7,*time8;

	/*時間*/
	gettimeofday(&tv1,NULL);
	time1=localtime(&tv1.tv_sec);

	int times;

	std::string flag_status;
	std::string body;
	bool flag = false;
	int i = 0, j = 0;
	int read_count = 1;
	int Barrier_COUNT = 0;
	std::mt19937 mt{std::random_device{}()};
	std::uniform_int_distribution<int> dist(0, 100);
	int rand = 0;

	/* IP アドレス、ポート番号、ソケット */
	char destination[] = "192.168.100.3";
	//char destination[] = "127.0.0.1";
	unsigned short port = 80;
	char httppath_COUNT[] = "/VC/MPI_Barrier_ID.php";
	char httphost[] = "192.168.100.3";
	//char httphost[] = "127.0.0.1";
	int dstSocket;
	int result; //戻り値

	/* sockaddr_in 構造体 */
	struct sockaddr_in dstAddr;

	/* 各種パラメータ */
	char toSendText[BUF_LEN];
	char buf[BUF_LEN + 1];
	int read_size;

	/* sockaddr_in 構造体のセット */
	memset(&dstAddr, 0, sizeof(dstAddr));
	dstAddr.sin_port = htons(port);
	dstAddr.sin_family = AF_INET;
	dstAddr.sin_addr.s_addr = inet_addr(destination);

	/* ソケット生成 */
	dstSocket = socket(AF_INET, SOCK_STREAM, 0);
	if (dstSocket < 0)
	{
		printf("ソケット生成エラー\n");
	}


	/*IDスタート時間*/
	gettimeofday(&tv2,NULL);
	time2=localtime(&tv2.tv_sec);
	printf("Barrier_initialize_%d_Time_%lf_sec\n", YOUR_ID , (tv2.tv_sec - tv1.tv_sec) + (tv2.tv_usec - tv1.tv_usec) * 1.0E-6);
	printf("Barrier_ID_%d_start_%02d.%06d_sec\n", YOUR_ID , time2 -> tm_sec , tv2.tv_usec);




	/* 接続 */
	result = connect(dstSocket, (struct sockaddr *)&dstAddr, sizeof(dstAddr));
	if (result < 0)
	{
		// printf("%d\n", GetLastError());
		printf("バインドエラー\n");
		return;
	}


	/*connect時間*/
	gettimeofday(&tv3,NULL);
	time3=localtime(&tv3.tv_sec);
	printf("Barrier_ID_connect_%d_Time_%lf_sec\n", YOUR_ID , (tv3.tv_sec - tv2.tv_sec) + (tv3.tv_usec - tv2.tv_usec) * 1.0E-6);


	printf("\n%s Connect OK\n", httppath_COUNT);

	body = "NUMPROCS=" + std::to_string(numprocs) + "&YOUR_ID=" + std::to_string(YOUR_ID) + "&ISOLATION=" + std::to_string(isolation);

	/* HTTP プロトコル生成 & サーバに送信 */

	printf("\nSending headers...");

	//http header [POST] or [GET]
	sprintf(toSendText, "POST %s?ID_%d HTTP/1.1\r\n", httppath_COUNT, YOUR_ID);
	send(dstSocket, toSendText, strlen(toSendText), 0);

	//Set Host
	sprintf(toSendText, "Host: %s:%d\r\n", httphost, port);
	send(dstSocket, toSendText, strlen(toSendText), 0);

	//POSTで変数を送信する場合、以下のコンテンツタイプを指定する
	sprintf(toSendText, "Content-Type: application/x-www-form-urlencoded\r\n");
	send(dstSocket, toSendText, strlen(toSendText), 0);

	//コンテンツの長さ（バイト）を指定する
	sprintf(toSendText, "Content-Length: %d\r\n", (int)body.length());
	send(dstSocket, toSendText, strlen(toSendText), 0);

	sprintf(toSendText, "Connection: Close\r\n");
	send(dstSocket, toSendText, strlen(toSendText), 0);

	sprintf(toSendText, "\r\n");
	send(dstSocket, toSendText, strlen(toSendText), 0);

	printf("OK\n");

	printf("\nSending bodys...");

	//rand = dist(mt) * 1000;
	//usleep(50000 + rand);

	// HTTP Body部の作成
	memset(toSendText, 0, sizeof(toSendText));
	sprintf(toSendText, "%s", body.c_str());
	send(dstSocket, toSendText, strlen(toSendText), 0);

	printf("OK\n\n");


	/*sendfunction時間*/
	gettimeofday(&tv4,NULL);
	time4=localtime(&tv4.tv_sec);
	printf("Barrier_ID_sendfunction_%d_Time_%lf_sec\n", YOUR_ID , (tv4.tv_sec - tv3.tv_sec) + (tv4.tv_usec - tv3.tv_usec) * 1.0E-6);


	printf("\nReceiving a response...");

	//Dump HTTP response
	while (1)
	{

		memset(buf, 0, sizeof(buf));
		read_size = recv(dstSocket, buf, BUF_LEN, 0);
		buf[BUF_LEN] = '\0';

		// レスポンスヘッダ削除のための処理
		if (flag == false)
		{

			flag = true;

			while (1)
			{
				if (buf[i] == '\r' && buf[i + 1] == '\n' && buf[i + 2] == '\r' && buf[i + 3] == '\n')
				{
					i += 4;
					break;
				}
				i++;
			}

			for (int j = 0; j < sizeof(buf); j++)
			{
				if (i < sizeof(buf))
				{
					buf[j] = buf[i];
				}
				else
				{
					buf[j] = '\0';
				}
				i++;
			}
			i = 0;
		}

		if (read_size > 0)
		{
			printf("%s",  buf);
		}
		else
		{
			break;
		}
	}

	printf("MPI_Barrierは %d回目です。\n", isolation);
	printf("\n\n\n");

	/* socket close */
	close(dstSocket);

	/*recvfunction時間*/
	gettimeofday(&tv5,NULL);
	time5=localtime(&tv5.tv_sec);
	printf("Barrier_ID_recvfunction_%d_Time_%lf_sec\n", YOUR_ID , (tv5.tv_sec - tv4.tv_sec) + (tv5.tv_usec - tv4.tv_usec) * 1.0E-6);
	printf("Barrier_ID_%d_Time_%lf_sec\n", YOUR_ID , (tv5.tv_sec - tv2.tv_sec) + (tv5.tv_usec - tv2.tv_usec) * 1.0E-6);


	char httppath_Res[] = "/VC/MPI_Barrier_FLAG.php";

	while (1)
	{

		/*FLAGスタート時間*/
		gettimeofday(&tv1,NULL);
		time1=localtime(&tv1.tv_sec);
		printf("Barrier_FLAG_%d_start_%02d.%06d_sec\n", YOUR_ID , time1 -> tm_sec , tv1.tv_usec);

		/* ソケット生成 */
		dstSocket = socket(AF_INET, SOCK_STREAM, 0);
		if (dstSocket < 0)
		{
			printf("ソケット生成エラー\n");
		}

		/* 接続 */
		result = connect(dstSocket, (struct sockaddr *)&dstAddr, sizeof(dstAddr));
		if (result < 0)
		{
			// printf("%d\n", GetLastError());
			printf("バインドエラー\n");
			return;
		}

		/*connect時間*/
		times++;
		gettimeofday(&tv2,NULL);
		time2=localtime(&tv2.tv_sec);
		printf("Barrier_FLAG_connect_%d_%d_Time_%lf_sec\n",times, YOUR_ID , (tv2.tv_sec - tv1.tv_sec) + (tv2.tv_usec - tv1.tv_usec) * 1.0E-6);


		printf("\n%s Connect OK\n", httppath_Res);

		body = "ISOLATION=" + std::to_string(isolation) + "&YOUR_ID=" + std::to_string(YOUR_ID);

		/* HTTP プロトコル生成 & サーバに送信 */

		printf("\nSending headers...");

		//http header [POST] or [GET]
		sprintf(toSendText, "POST %s?ID_%d HTTP/1.1\r\n", httppath_Res, YOUR_ID);
		send(dstSocket, toSendText, strlen(toSendText), 0);

		//Set Host
		sprintf(toSendText, "Host: %s:%d\r\n", httphost, port);
		send(dstSocket, toSendText, strlen(toSendText), 0);

		//POSTで変数を送信する場合、以下のコンテンツタイプを指定する
		sprintf(toSendText, "Content-Type: application/x-www-form-urlencoded\r\n");
		send(dstSocket, toSendText, strlen(toSendText), 0);

		//コンテンツの長さ（バイト）を指定する
		sprintf(toSendText, "Content-Length: %d\r\n", (int)body.length());
		send(dstSocket, toSendText, strlen(toSendText), 0);

		sprintf(toSendText, "Connection: Close\r\n");
		send(dstSocket, toSendText, strlen(toSendText), 0);

		sprintf(toSendText, "\r\n");
		send(dstSocket, toSendText, strlen(toSendText), 0);

		printf("OK\n");

		printf("\nSending bodys...");

		//rand = dist(mt) * 1000;
		//usleep(50000 + rand);

		// HTTP Body部の作成
		memset(toSendText, 0, sizeof(toSendText));
		sprintf(toSendText, "%s", body.c_str());
		send(dstSocket, toSendText, strlen(toSendText), 0);

		printf("OK\n\n");


		/*sendfunction 時間*/
		gettimeofday(&tv3,NULL);
		time3=localtime(&tv3.tv_sec);
		printf("Barrier_FLAG_sendfunction_%d_%d_Time_%lf_sec\n",times, YOUR_ID , (tv3.tv_sec - tv2.tv_sec) + (tv3.tv_usec - tv2.tv_usec) * 1.0E-6);



		printf("\nReceiving a response...");

		flag = false;

		//Dump HTTP response
		while (1)
		{

			memset(buf, 0, sizeof(buf));
			read_size = recv(dstSocket, buf, BUF_LEN, 0);
			buf[BUF_LEN] = '\0';

			if (flag == false)
			{

				flag = true;

				while (1)
				{
					if (buf[i] == '\r' && buf[i + 1] == '\n' && buf[i + 2] == '\r' && buf[i + 3] == '\n')
					{
						i += 4;
						break;
					}
					i++;
				}

				for (int j = 0; j < sizeof(buf); j++)
				{
					if (i < sizeof(buf))
					{
						buf[j] = buf[i];
					}
					else
					{
						buf[j] = '\0';
					}
					i++;
				}
				i = 0;
			}

			if (read_size > 0)
			{
				printf("%s", buf);
				flag_status = buf;
			}
			else
			{
				break;
			}
		}

		/* socket close */
		close(dstSocket);
		//fclose(Barrier_fp);


		/*recvfunction 時間*/
		gettimeofday(&tv4,NULL);
		time4=localtime(&tv4.tv_sec);
		printf("Barrier_FLAG_%d_%d_end_%02d.%06d_sec\n",times, YOUR_ID , time4 -> tm_sec , tv4.tv_usec);
		printf("Barrier_FLAG_recvfunction_%d_%d_Time_%lf_sec\n",times, YOUR_ID , (tv4.tv_sec - tv3.tv_sec) + (tv4.tv_usec - tv3.tv_usec) * 1.0E-6);
		printf("Barrier_FLAG_%d_%d_Time_%lf_sec\n",times,YOUR_ID , (tv4.tv_sec - tv1.tv_sec) + (tv4.tv_usec - tv1.tv_usec) * 1.0E-6);

		if (!strcmp(flag_status.c_str(), "NG\n"))
		{
			printf("%s\n\n", flag_status.c_str());
		}

		if (!strcmp(flag_status.c_str(), "OK\n"))
		{
			printf("%s\n", flag_status.c_str());
			printf("MPI_Barrier completed\n\n");
			break;
		}
		usleep(100000);
	}
	gettimeofday(&tv6,NULL);
	time6=localtime(&tv6.tv_sec);
	printf("Barrier_ALL_FLAG_%d_end_%02d.%06d_sec\n", YOUR_ID , time6 -> tm_sec , tv6.tv_usec);
	printf("Barrier_ALL_FLAG_%d_Time_%lf_sec\n",YOUR_ID , (tv6.tv_sec - tv5.tv_sec) + (tv6.tv_usec - tv5.tv_usec) * 1.0E-6);
}


extern "C" void barrier_(int *YOUR_ID, int *numprocs, int *isolation)
{
  barrier(*YOUR_ID, *numprocs, *isolation);
}
