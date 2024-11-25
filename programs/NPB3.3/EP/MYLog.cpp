#include <cstdio>
#include <cstdlib>
#include <cstdarg>
#include <ctime>
#include <cerrno>

static FILE *gfp=NULL;

#ifdef MYCLANG
#define MYEXTERN_C extern "C"
#else
#define MYEXTERN_C
#endif

MYEXTERN_C void log_fopen(int id){
	int gfname_len=40;
	char gfname[gfname_len];

	long unix_time=time(NULL);
	int write_len=snprintf(gfname,gfname_len,"log_%d_%ld.txt",id,unix_time);
	if(write_len<0 || write_len>gfname_len){
		printf("error: file name is too long (log_%d_%ld.txt)\n",id,unix_time);
		exit(1);
	}
	if((gfp=fopen(gfname,"w"))==NULL){
		perror("File open error");
		exit(1);
	}
	printf("id%d: file open\n",id);
}

MYEXTERN_C void log_fclose(){
	fclose(gfp);
}

MYEXTERN_C void log_printf(const char *format,...){
	va_list arg;
	va_start(arg,format);
	vfprintf(gfp,format,arg);
	va_end(arg);
}

// fortran用コード
#ifdef MYFORTRAN
extern "C" void log_fopen_(int* id)
{
	log_fopen(*id);
}

extern "C" void log_fclose_()
{
	fclose(gfp);
}

extern "C" void log_fprintf_(int* id, char *func, double *time, int func_len){
	char tmp[20];
	for(int i=0;i<func_len;i++){
		tmp[i]=func[i];
	}
	tmp[func_len]='\0';
	fprintf(gfp,"ID%d: %s %lfsec\n",*id, tmp, *time);
}
#endif