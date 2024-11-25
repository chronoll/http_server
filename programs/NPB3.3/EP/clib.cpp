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
void print_cnt(const char a[], const char b[], const char c[])
{
   	printf("look_before mid befor: %s %s %s\n", a,b,c );

	char d[100];
	strncpy(d,b,strlen(b)-strlen(a));

   	printf("look_after mid befor: %s %s %s %s\n", a,b,c,d);

	if (!strcmp(a, "aa"))
	{
		if (!strcmp(d, "bbb"))
   			printf("look_final: %s %s %s\n", a,b,c );
	}
}


// function for Fortran subroutine
extern "C" void print_cnt_(const char a[],const char b[],const char c[] )
{
   	printf("look_:  %s %s %s\n",a,b,c);
	print_cnt(a, b, c);
}

