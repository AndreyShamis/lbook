import os
import random
import socket
import string
import time
import threading
import json
import multiprocessing
import logging
from threading import Thread
from .logbook_cycle import LogBookCycle
from .logbook_api import LogbookAPI, LOGBOOK_DOMAIN
from .log_book_post_encoder import LogBookPostEncoder
import urllib.request
try:
    import platform
except:
    pass
try:
    import psutil
    import typing
except:
    pass


TOKEN_LEN = 30
MAX_TOKEN_LEN = 150
DEBUG_LOGBOOK_DOMAIN = "127.0.0.1:8080"
URL_PATTERN = "http://{}/upload/new_cli"
SUITE_EXECUTION_URL_PATTERN = "http://{}/upload/create_suite_execution"
SE_CLOSE_URL_PATTERN = "http://{}/suites/close/{}"
MIN_TOKEN_LEN = 20
UPLOAD_TIMEOUT = 180
SUITE_CREATE_TIMEOUT = 120
SUITE_CLOSE_TIMEOUT = 60
MAX_UPLOADS_SAME_TIME = 10   # Max uploads in same time
WAIT_TIME_BEFORE_START_UPLOAD = 10   # Time to wait for MAX_UPLOADS_SAME_TIME exit
MAX_UPLOAD_SIZE = 15  # type: int   # max file size in megabytes


def get_cpu_load():
    # type: () -> float
    try:
        import psutil
        return psutil.cpu_percent()
    except Exception as ex:
        pass
    try:
        proc_number = multiprocessing.cpu_count()
        _value = float(os.getloadavg()[0] * proc_number)
        if _value > 100:
            _value = 100
        if _value < 0:
            _value = 0
        return _value
    except Exception as ex:
        pass

    return 11.0


def get_hostname():
    # type: () -> str
    ret = ''
    try:
        ret = f'{socket.gethostname()}'
    except Exception as ex:
        try:
            ret = f'{os.uname()[1]}'
        except Exception as ex:
            pass
    return ret.strip()


def get_cpu_load_avg(avg_counter=10):
    # type: (int) -> float
    summary = 0
    if avg_counter < 1:
        avg_counter = 1
    for x in range(0, avg_counter):
        summary += get_cpu_load()
        time.sleep(0.001)
    return round(summary/avg_counter, 2)


def is_jenkins():
    # type: () -> bool
    ret = False
    try:
        ret_str = os.environ.get('BUILD_ID', '')
        if ret_str != '':
            ret = True
    except Exception as ex:
        print(ex)
    return ret


class LogBookUploader(object):
    """
    Logbook Uploader object
    """
    # url = LOGBOOK_URL   # DEBUG_LOGBOOK_URL
    __DEF_FILE = 'debug/autoserv.DEBUG'

    def __init__(self, setup_name=None, cycle_name='', token=None, url=None, domain=LOGBOOK_DOMAIN, *args, **dargs):
        # type: (str, str, str, str, str, int, dict) -> None
        """
        """
        self.domain = domain
        if url is not None:
            self.url = url
        else:
            self.url = URL_PATTERN.format(self.domain)
        self.suite_execution_url = SUITE_EXECUTION_URL_PATTERN.format(self.domain)  # type: str
        self.__threads = []  # type: typing.List[Thread]
        self.__token = None
        self.__user = None
        self.__setup_name = None
        self.__cycle_name = None
        self.__build_name = None
        self.__test_metadata = None
        self.__cycle_metadata = ''
        self.progress = 0
        self.__pre_post_aw_sem = threading.Semaphore()
        self.test_count = 0
        self.set_token(token)
        self.set_cycle_name(cycle_name)
        self.set_setup_name(setup_name)
        self.timeout_count = 0
        self.failures_count = 0
        self.uploads_success = 0
        self._suite_execution_id = 0  # type: int
        self.__is_commit = False
        self.exit = False
        async_thread = threading.Thread(target=self.threads_keeper)
        async_thread.setName("threads_keeper")
        async_thread.start()

    def current_count(self):
        return len(self.__threads)

    def threads_keeper(self):
        while not self.exit:
            try:
                time.sleep(0.05)
                if self.get_progress() < 1:
                    for _t in self.__threads:
                        if not _t.is_alive() and _t.ident is not None and _t.ident > 0:
                            _t.join(0)
                            self.__threads.remove(_t)
                            break
                        if not _t.is_alive() and _t.ident is None:
                            if self.get_progress() == 0:
                                _t.start()
                                time.sleep(0.1)
                            break
                        if self.get_progress() > 0:
                            break
            except Exception as ex:
                logging.exception(ex)

    def get_progress(self):
        # type: () -> int
        self.__pre_post_aw_sem.acquire()
        ret = self.progress
        self.__pre_post_aw_sem.release()
        return ret

    def is_commit(self):
        # type: () -> bool
        return self.__is_commit

    def set_as_commit(self):
        # type: () -> None
        """
        Set true if execution from CI commit
        """
        self.__is_commit = True

    def get_suite_execution_id(self):
        # type: () -> int
        return self._suite_execution_id

    def __get_post_fields(self, test=None):
        # type: (object) -> list
        fields = [
            ('return_urls_only', 'true'),
            ('token', self.get_token()),
            ('setup', self.get_setup_name()),
            ('user', self.get_user()),
            ('tests_count', self.test_count),
            ('build', self.get_build_name()),
            ('test_metadata', self.get_test_metadata()),
            ('cycle_metadata', self.get_cycle_metadata()),
            ('cycle', self.get_cycle_name()),
            ('test_name', ''),
            ('control_file', ''),
            ('test_weight', 0),
            ('test_timeout', 0),
            ('test_exit_code', 0),  # 0
            ('test_result', 'UKNOWN'),  # 0
            ('suite_execution_id', self.get_suite_execution_id())
        ]
        try:
            #fields.append(('test_result', 'UKNOWN'))  # PASSED
        except:
            pass
        return fields

    def save(self):
        """
        Save LogBookUploader into LogBookCycle object
        :return: file name
        """
        cycle = LogBookCycle()
        cycle.token = self.get_token()
        cycle.cycle_name = self.get_cycle_name()
        cycle.setup_name = self.get_setup_name()
        cycle.test_count = self.test_count
        cycle.build_name = self.get_build_name()
        return cycle.save()

    @classmethod
    def load(cls, file_name):
        # type: (LogBookUploader, str) -> LogBookUploader
        """
        Load LogBookUploader from LogBookCycle object
        """
        cycle = LogBookCycle()
        cycle.load(file_name)
        new_cls = cls(setup_name=cycle.setup_name, cycle_name=cycle.cycle_name, token=cycle.token)
        # new_cls.set_token(cycle.token)
        # new_cls.set_cycle_name(cycle.cycle_name)
        # new_cls.set_setup_name(cycle.setup_name)
        new_cls.set_build_name(cycle.build_name)
        new_cls.test_count = cycle.test_count
        return new_cls

    def reset(self):
        # type: () -> None
        self.set_token(LogBookUploader.get_random_string(TOKEN_LEN))

    def set_user(self, new_user):
        # type: (str) -> LogBookUploader
        if new_user is None:
            new_user = ""
        self.__user = str(new_user)
        return self

    def get_user(self):
        # type: () -> str
        if self.__user is None:
            self.set_user("")
        return self.__user

    def set_setup_name(self, new_setup_name):
        # type: (str) -> LogBookUploader
        if new_setup_name is None:
            new_setup_name = ""
        self.__setup_name = str(new_setup_name)
        return self

    def get_setup_name(self):
        # type: () -> str
        return self.__setup_name

    def set_token(self, token=None):
        # type: (str) -> LogBookUploader
        if token is None:
            self.__token = LogBookUploader.get_random_string(TOKEN_LEN)
        else:
            self.__token = token
        return self

    def get_token(self):
        # type: () -> str
        return self.__token

    def reset_cycle_metadata(self):
        # type: () -> None
        """
        Reset cycle metadata
        """
        self.set_cycle_metadata('')

    def set_cycle_metadata(self, metadata):
        # type: (str) -> None
        """
        Set cycle metadata
        """
        self.__cycle_metadata = metadata

    def add_cycle_metadata(self, key, value):
        # type: (str, str) -> None
        """
        Add cycle metadata
        """
        if len(self.__cycle_metadata) == 0:
            self.__cycle_metadata = f'{key}::{value};;'
        else:
            self.__cycle_metadata = f'{self.__cycle_metadata};;{key}::{value};;'
        self.__cycle_metadata = self.__cycle_metadata.replace(';;;;', ';;')

    def get_cycle_metadata(self):
        # type: () -> str
        if self.__cycle_metadata is None:
            return ''
        return self.__cycle_metadata

    def set_test_metadata(self, metadata):
        # type: (str) -> None
        """
        Set test metadata
        """
        self.__test_metadata = metadata

    def get_test_metadata(self):
        # type: () -> str
        if self.__test_metadata is None:
            return ''
        return self.__test_metadata

    def get_build_name(self):
        # type: () -> str
        if self.__build_name is None:
            self.set_build_name("")
        return self.__build_name

    def set_build_name(self, build_name):
        # type: (str) -> None
        if build_name is None:
            build_name = ""
        self.__build_name = build_name

    def get_cycle_name(self):
        # type: () -> str
        return self.__cycle_name

    def set_cycle_name(self, cycle_name):
        # type: (str) -> LogBookUploader
        self.__cycle_name = str(cycle_name)
        return self

    def close_suite_execution(self, suite_execution, print_response=False, verbose=True, *args, **kwargs):
        # type: (dict, bool, bool, list, dict) -> bool
        """
        Uploads log file to logbook site
        """
        if self._suite_execution_id > 0:
            se = {}
            try:
                suite = kwargs.get('suite', None)  # type: Suite
                files = []
                headers = {'Content-Type': 'application/json'}
                proxy_handler = urllib.request.ProxyHandler({})
                opener = urllib.request.build_opener(proxy_handler)
                data = json.dumps(se)
                data = str(data)    # Convert to String
                data = data.encode('utf-8')     # Convert string to byte
                _url = SE_CLOSE_URL_PATTERN.format(self.domain, self._suite_execution_id)  # type: str
                the_request = urllib.request.Request(url=_url, data=data, headers=headers)
                try:
                    self.progress += 1
                    response = opener.open(the_request, timeout=SUITE_CLOSE_TIMEOUT)
                    readed = ''
                    if response.code == 200:
                        try:
                            readed = LogBookUploader.__response_to_one_line(response)
                            if suite:
                                self._suite_execution_id = suite.logbook_end_handler(readed)
                            logging.log(22, 'LogBook Suite Tests http://{dom}/cycle/suiteid/{sid} \t'
                                                  ' Suite Info http://{dom}/suites/cycle/{sid} '.format(
                                dom=LOGBOOK_DOMAIN, sid=self._suite_execution_id))
                        except Exception as ex:
                            logging.exception(ex)
                    if print_response:
                        logging.error(readed)
                except (socket.timeout, Exception) as ex:
                    if hasattr(ex, 'args') and len(ex.args) and 'Name or service not known' in ex.args[0]:
                        return True
                    logging.error(f"[close_suite_execution]: {ex}")
                finally:
                    self.progress -= 1
                return True
            except Exception as ex:
                if verbose:
                    logging.error(f'Failed is suiteExecution closer {ex}')
                    logging.exception(ex)
                    logging.debug(f'{ex}')
        return False

    def create_suite_execution(self, suite_execution, verbose=True, *args, **kwargs):
        # type: (dict, bool, list, dict) -> bool
        """
        Uploads log file to logbook site
        """
        se = {}
        try:
            se = suite_execution.copy()
            suite = kwargs.get('suite', None)  # type: Suite
            try:
                se['hostname'] = get_hostname()
                se['host_uptime'] = int(psutil.boot_time())
                mem = psutil.virtual_memory()
                se['host_memory_total'] = int(mem.total/1024/1042)
                se['host_memory_free'] = int(mem.free/1024/1042)

            except:
                pass
            try:
                se['host_cpu_usage'] = get_cpu_load_avg()
                se['host_system'] = platform.system()
                se['host_release'] = platform.release()
                se['host_version'] = platform.version()
                se['host_python_version'] = platform.python_version()
                se['host_user'] = os.getlogin()
                se['host_cpu_count'] = os.cpu_count()
            except:
                pass

            try:
                se['product_version'] = suite_execution['prod_v']
                del se['prod_v']
            except:
                pass
            try:
                project_list_ready = []
                project = os.environ.get('GERRIT_PROJECT', '')  # type: str
                if project.strip():
                    se['GERRIT_PROJECT'] = project.strip()
            except:
                pass
            try:
                clusters_list_ready = []
                clusters = os.environ.get('CLUSTER', '')  # type: str
                if clusters:
                    clusters_list = clusters.split(',')  # type: list
                    if clusters_list:
                        for x in clusters_list:
                            if x.strip() not in clusters_list_ready:
                                clusters_list_ready.append(x.strip())
                se['clusters'] = clusters_list_ready
            except:
                pass
            try:
                se['description'] = suite_execution['desc']
                del se['desc']
            except:
                pass
            try:
                se['components'] = suite_execution['_components']
                del se['_components']
            except:
                pass

            gb = os.environ.get('GERRIT_BRANCH')  # type: str
            mr = os.environ.get('MANIFEST_REVISION')  # type: str
            if gb and len(gb) > 3:
                se['GERRIT_BRANCH'] = gb
            elif mr and len(mr) > 3:
                se['GERRIT_BRANCH'] = mr
            se['is_jenkins'] = is_jenkins()
            if is_jenkins():
                try:
                    gpsn = os.environ.get('GERRIT_PATCHSET_NUMBER', '')  # type: str
                    gcn = os.environ.get('GERRIT_CHANGE_NUMBER', '')  # type: str
                    gci = os.environ.get('GERRIT_CHANGE_ID', '')  # type: str
                    if gcn != '' and int(gcn) > 0 and gci != '' and gpsn != '' and gb != '':
                        se['PRE_COMMIT'] = f'{gb}_{gci}_{gcn}_{gpsn}'  # TODO gpsn - not used?
                except:
                    pass

            headers = {'Content-Type': 'application/json'}
            lbk_api = LogbookAPI()
            try:
                self.progress += 1
                r = lbk_api.urlopen_lbk(
                    # url=self.suite_execution_url,
                    url='upload/create_suite_execution',
                    method='POST',
                    data=se,
                    headers=headers,
                    verbose=False,
                    sleep_on_error=0,
                    timeout=SUITE_CREATE_TIMEOUT
                )
                filters = []
                if 'FILTERS' in r:
                    filters = r['FILTERS']
                    if suite:
                        suite.set_filters(filters)
                if 'SUITE_EXECUTION_ID' in r:

                    sid = r['SUITE_EXECUTION_ID']
                    if suite:
                        self._suite_execution_id = suite.set_suite_execution_id(sid)
                    logging.warning(f'LogBook Suite Execution http://{LOGBOOK_DOMAIN}/suites/show/{sid} '
                                    f'for {se["summary"]}. Filters {len(filters)}')
            except (socket.timeout, Exception) as ex:
                if hasattr(ex, 'args') and len(ex.args) and 'Name or service not known' in ex.args[0]:
                    return True
                logging.error(f'[create_suite_execution]: {ex}')
            finally:
                self.progress -= 1
            return True
        except Exception as ex:
            if verbose:
                logging.error(f'Failed is suiteExecution creation {ex}')
                logging.exception(ex)
                logging.debug(f'{se}')
        return False

    def post(self, logs_path, print_response=False, verbose=False, **dargs):
        # type: (str, bool, bool, dict) -> bool
        """
        Uploads log file to logbook site
        """
        self.__pre_post_aw_sem.acquire()
        self.progress += 1
        self.test_count += 1
        self.__pre_post_aw_sem.release()

        if not os.path.isdir(logs_path):
            logging.error(f'Provided path not exist : [{logs_path}].')
            return False
        upload_file = os.path.join(logs_path, LogBookUploader.__DEF_FILE)
        if not os.path.exists(upload_file):
            logging.error(f'File for upload not found : [{upload_file}].')
            return False
        files = [('file', os.path.basename(upload_file), upload_file)]

        test = dargs.get('test', None)
        suite = dargs.get('suite', None)
        fields = self.__get_post_fields(test)
        content_type, body = LogBookPostEncoder().encode(fields, files)
        headers = {'Content-Type': content_type}
        # For disable proxy
        proxy_handler = urllib.request.ProxyHandler({})
        opener = urllib.request.build_opener(proxy_handler)
        the_request = urllib.request.Request(url=self.url, data=body, headers=headers)
        try:

            response = opener.open(the_request, timeout=UPLOAD_TIMEOUT)
            # response = urllib2.urlopen(the_request, timeout=UPLOAD_TIMEOUT)
            readed = ''
            if response.code == 200:
                try:
                    readed = LogBookUploader.__response_to_one_line(response)
                    if test is not None:
                        test.logbook_end_handler(readed)
                    if self.test_count % 20 == 0:
                        self.reset_cycle_metadata()
                except Exception as ex:
                    logging.exception(ex)
            if print_response:
                if test is not None and suite is not None and 'Link to detailed' in readed:
                    logging.error(f'{readed} (for test:[{test.get_testname()}] [{suite.get_short_name()}])')
                else:
                    logging.error(f'{readed}')

                LogBookUploader.__print_response(response, f'File {self.test_count}')
            if verbose:
                logging.info(f"File {upload_file} uploaded.")
            self.uploads_success += 1
        except socket.timeout as ex:
            self.timeout_count += 1
            self.failures_count += 1
            logging.error(f'[LogBookUploader:POST] timeout found... Number of timeouts {self.timeout_count}')
        except:
            self.failures_count += 1
        finally:
            self.__pre_post_aw_sem.acquire()
            self.progress -= 1
            self.__pre_post_aw_sem.release()
        return True

    def post_async(self, logs_path, print_response=False, verbose=False, *args, **dargs):
        # type: (str, bool, bool, list, dict) -> bool
        """
        """
        log_size_m = 0
        try:
            try:
                tmp_path  = os.path.join(logs_path, 'debug/autoserv.DEBUG')
                log_size = os.path.getsize(tmp_path)
                log_size_m = round(log_size / 1024 / 1024, 3)
                if log_size_m > MAX_UPLOAD_SIZE:
                    logging.warning('Skip lbk upload for {logs_path} due size {log_size_m} > {MAX_UPLOAD_SIZE}Mb')
                    return False
            except Exception as ex:
                logging.exception(ex)
            if verbose:
                logging.info(f"Uploading file {logs_path} in thread.")
            async_thread = threading.Thread(target=self.post, args=(logs_path, print_response, verbose), kwargs=dargs)
            async_thread.setName(f'{self.test_count + 1}')
            self.__threads.append(async_thread)
            if log_size_m > 5:
                time.sleep(15)
        except Exception as ex:
            logging.exception(str(ex))
            return False
        return True

    def wait_for_close(self, wait_time=300, verbose=True):
        # type: (int, bool) -> bool
        res = True
        if self.get_progress() > 0:
            time.sleep(0.1)
        if verbose:
            logging.info(f"Threads opened {len(self.__threads)}. Progress={self.get_progress()}.")
            if self.get_progress() > 0:
                time.sleep(0.05)
        start_wait = time.time()
        while (self.get_progress() or self.current_count()) and start_wait + wait_time + 10 > time.time():
            if self.get_progress() > 0:
                time.sleep(0.1)
                while start_wait + wait_time > time.time() and self.get_progress() > 0:
                    time.sleep(0.2)
                    if verbose and self.get_progress() > 0:
                        time.sleep(0.1)
                        if self.get_progress():
                            logging.info(f"{self.get_progress()} thread in progress, "
                                         f"wait for {start_wait + wait_time - time.time()} seconds.")
                            time.sleep(1)
            else:
                time.sleep(0.1)
        self.exit = True
        if start_wait + wait_time < time.time() and self.get_progress() > 0:
            logging.error(f"Timeout on wait, in progress {self.get_progress()} uploads.")
            res = False
        try:
            for th in self.__threads:
                try:
                    if th.is_alive():
                        th.join(0)
                except Exception as ex:
                    logging.exception(ex)
        except Exception as ex:
            logging.exception(ex)
        return res

    @staticmethod
    def __print_response(response, r_name=''):
        if len(r_name) > 0:
            r_name = f'{r_name}: '
        try:
            for line in response:
                tmp_line = line.replace('\n', ' ').replace('\r', '')
                print(f'{r_name}{tmp_line}')
        except Exception:
            pass

    @staticmethod
    def __response_to_one_line(response):
        ret = ''
        try:
            for line in response:
                try:
                    if isinstance(line, bytes):
                        line = line.decode('utf8')
                except:
                    pass
                tmp_line = line.replace('\n', ' ').replace('\r', '')
                ret = f'{ret}{tmp_line}'
        except Exception:
            pass
        return ret

    @staticmethod
    def get_random_string(length=50, silent=True):
        # type: (int, bool) -> str
        """
        Generate random string
        :param length: Length of string
        :param silent: ignore errors
        :return:
        """
        if length < MIN_TOKEN_LEN:
            if silent:
                length = 30
            else:
                raise Exception(f'The min str len should be {MIN_TOKEN_LEN}, but {length} provided')
        if length > MAX_TOKEN_LEN:
            if silent:
                length = 31
            else:
                raise Exception(f'The max str len should be {MAX_TOKEN_LEN}, but {length} provided')
        return ''.join(random.choice(string.ascii_uppercase + string.digits) for _ in range(length))
