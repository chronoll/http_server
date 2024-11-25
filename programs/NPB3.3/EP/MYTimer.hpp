#ifndef MYTIMER_HPP
#define MYTIMER_HPP

class MYTimer{
	protected:
		struct timeval start_t,stop_t,date_t;
		double diff,lap,total;
		int cnt;
		char func[16];
		int id;
	public:
		MYTimer(const char *func,int id);
		void start();
		void stop();
		void clear_lap();
		void clear_total();
		void add_total();
		void date();
		void print_diff(const char *section, const char *subsection);
		void print_and_clear_lap(const char *section);
		void print_and_clear_total(const char *section);
		void print_date(const char *section, const char *subsection);
		void count_up();
};

#endif
