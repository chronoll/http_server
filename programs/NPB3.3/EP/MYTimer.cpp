#include<cstdio>
#include<cstring>
#include<sys/time.h>
#include<time.h>

#include "MYTimer.hpp"
#include "MYLog.h"

MYTimer::MYTimer(const char *func,int id){
	this->lap=0;
	this->total=0;
	this->cnt=0;
	strcpy(this->func,func);
	this->id=id;
}
void MYTimer::start(){
	gettimeofday(&this->start_t,NULL);
}
void MYTimer::stop(){
	gettimeofday(&this->stop_t,NULL);
	this->diff=(stop_t.tv_sec-start_t.tv_sec)+(stop_t.tv_usec-start_t.tv_usec)*1.0E-6;
	this->lap+=diff;
}
void MYTimer::clear_lap(){
	this->lap=0;
}
void MYTimer::clear_total(){
	this->clear_lap();
	this->cnt=0;
	this->total=0;
}
void MYTimer::add_total(){
	this->total+=this->lap;
}
void MYTimer::date(){
	gettimeofday(&this->date_t,NULL);
}
void MYTimer::print_diff(const char *section, const char *subsection){
	logprintf("%s_%s_%s_%d_%d_Time_%lf_sec\n",
	this->func,
	section,
	subsection,
	this->cnt,
	this->id,
	this->diff);
}
void MYTimer::print_and_clear_lap(const char *section){
	logprintf("%s_%s_lap_%d_%d_Time_%lf_sec\n",
	this->func,
	section,
	this->cnt,
	this->id,
	this->lap);
	this->add_total();
	this->clear_lap();
}
void MYTimer::print_and_clear_total(const char *section){
	if(total==0) this->add_total();
	logprintf("%s_%s_total_%d_Time_%lf_sec\n",
	this->func,
	section,
	this->id,
	this->total);
	this->clear_total();
}
void MYTimer::print_date(const char *section, const char *subsection){
	struct tm *d=localtime(&this->date_t.tv_sec);
	logprintf("%s_%s_%s_%d_date_%04d/%02d/%02d:%02d:%02d:%02d.%06lu\n",
		this->func,
		section,
		subsection,
		this->id,
		d->tm_year+1900,
		d->tm_mon,
		d->tm_mday,
		d->tm_hour,
		d->tm_min,
		d->tm_sec,
		this->date_t.tv_usec);
}
void MYTimer::count_up(){
	this->cnt++;
}