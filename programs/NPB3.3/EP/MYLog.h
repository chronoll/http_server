#ifndef MYLOG_H
#define MYLOG_H

#ifdef MYLOG
// extern FILE *gfp;
#define logprintf(...) log_printf(__VA_ARGS__)
#define logfopen(x) log_fopen(x)
#define logfclose() log_fclose()

#ifdef MYCLANG
#define MYEXTERN_C extern "C"
#else
#define MYEXTERN_C
#endif

MYEXTERN_C void log_fopen(int id);
MYEXTERN_C void log_fclose();
MYEXTERN_C void log_printf(const char* format,...);
#else
#define logprintf(...) printf(__VA_ARGS__)
#define logfopen(x)
#define logfclose()
#endif

#endif