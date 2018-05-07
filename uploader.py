import codecs
import io
import logging
import mimetypes
import os
import random
import socket
import string
import sys
import time
import urllib2
import uuid
import threading
import pickle
from typing import List
from threading import Thread

TOKEN_LEN = 30
MAX_TOKEN_LEN = 150
DEBUG_LOGBOOK_URL = "http://127.0.0.1:8080/upload/new_cli"
LOGBOOK_URL = "http://logbook.anshamis.com/upload/new_cli"
MIN_TOKEN_LEN = 20
UPLOAD_TIMEOUT = 120
WAIT_TH_COUNT = 1   # Max uploads in same time
WAIT_TH_MAX_TIME = 20   # Time to wait for WAIT_TH_COUNT exit


class MultipartFormDataEncoder(object):
    def __init__(self):
        self.boundary = uuid.uuid4().hex
        self.content_type = 'multipart/form-data; boundary={}'.format(self.boundary)

    @classmethod
    def u(cls, s):
        """

        :param s:
        :return:
        """
        if sys.hexversion < 0x03000000 and isinstance(s, str):
            s = s.decode('utf-8')
        if sys.hexversion >= 0x03000000 and isinstance(s, bytes):
            s = s.decode('utf-8')
        return s

    def iter(self, fields, files):
        """
        fields is a sequence of (name, value) elements for regular form fields.
        files is a sequence of (name, filename, file-type) elements for data to be uploaded as files
        Yield body's chunk as bytes
        :param fields:
        :param files:
        :return:
        """
        encoder = codecs.getencoder('utf-8')
        for (key, value) in fields:
            key = self.u(key)
            yield encoder('--{}\r\n'.format(self.boundary))
            yield encoder(self.u('Content-Disposition: form-data; name="{}"\r\n').format(key))
            yield encoder('\r\n')
            if isinstance(value, int) or isinstance(value, float):
                value = str(value)
            yield encoder(self.u(value))
            yield encoder('\r\n')
        for (key, filename, fpath) in files:
            key = self.u(key)
            filename = self.u(filename)
            yield encoder('--{}\r\n'.format(self.boundary))
            yield encoder(self.u('Content-Disposition: form-data; name="{}"; filename="{}"\r\n').format(key, filename))
            yield encoder('Content-Type: {}\r\n'.format(
                mimetypes.guess_type(filename)[0] or 'application/octet-stream'))
            yield encoder('\r\n')
            with open(fpath, 'rb') as fd:
                buff = fd.read()
                yield (buff, len(buff))
            yield encoder('\r\n')
        yield encoder('--{}--\r\n'.format(self.boundary))

    def encode(self, fields, files):
        body = io.BytesIO()
        for chunk, chunk_len in self.iter(fields, files):
            body.write(chunk)
        return self.content_type, body.getvalue()


class LogBookCycle(object):
    """
    LogBookCycle object for keep LogBookUploader information
    """
    __FILE_PREFIX = 'lb_'

    def __index__(self):
        self.token = None
        self.cycle_name = None
        self.setup_name = None
        self.build_name = None
        self.test_count = 0

    @staticmethod
    def __clean_string(s):
        """
        Clean string and prepare the string for file name
        :param s:
        :return:
        """
        valid_chars = "-_.() {}{}".format(string.ascii_letters, string.digits)
        tmp_str = ''.join(c for c in s if c in valid_chars)
        tmp_str = tmp_str.replace(' ', '_')
        return tmp_str

    def get_file_name(self):
        """
        Create file name from props
        :return: File name used in file to save data
        """
        cycle_name_tmp = LogBookCycle.__clean_string(self.cycle_name)
        token_tmp = LogBookCycle.__clean_string(self.token)
        return '{}{}_{}.lbk'.format(self.__FILE_PREFIX, cycle_name_tmp, token_tmp)

    def save(self):
        """
        Save object into file
        :return: File name
        """
        save_f_name = self.get_file_name()
        file_obj = open(save_f_name, 'w')
        file_obj.write(pickle.dumps(self.__dict__))
        file_obj.close()
        return save_f_name

    def load(self, file_name):
        """
        Load data from file
        :param file_name:
        """
        file_obj = open(file_name, 'r')
        data_pickle = file_obj.read()
        file_obj.close()
        self.__dict__ = pickle.loads(data_pickle)


class LogBookUploader(object):
    """
    Logbook Uploader object
    """
    url = LOGBOOK_URL   # DEBUG_LOGBOOK_URL
    __DEF_FILE = 'debug/autoserv.DEBUG'

    def __init__(self, setup_name=None, cycle_name='', token=None):
        self.__threads = []  # type: List[Thread]
        self.__token = None
        self.__setup_name = None
        self.__cycle_name = None
        self.__build_name = None
        self.progress = 0
        self.__post_async_waits = 0
        self.test_count = 0
        self.set_token(token)
        self.set_cycle_name(cycle_name)
        self.set_setup_name(setup_name)
        self.timeout_count = 0
        self.failures_count = 0
        self.uploads_success = 0

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
        """
        Load LogBookUploader from LogBookCycle object
        :param file_name:
        :return: LogBookUploader
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
        self.set_token(LogBookUploader.get_random_string(TOKEN_LEN))

    def set_setup_name(self, new_setup_name):
        assert not (new_setup_name is None), "Setup name cannot be empty"
        assert not (len(str(new_setup_name)) < 2), "Short setup name"
        self.__setup_name = str(new_setup_name)
        return self

    def get_setup_name(self):
        return self.__setup_name

    def set_token(self, token=None):
        if token is None:
            self.__token = LogBookUploader.get_random_string(TOKEN_LEN)
        else:
            self.__token = token
        return self

    def get_token(self):
        return self.__token

    def get_build_name(self):
        if self.__build_name is None:
            return self.get_cycle_name()
        return self.__build_name

    def set_build_name(self, build_name):
        if build_name is None or len(build_name) < 3:
            build_name = self.get_setup_name()
        self.__build_name = build_name

    def get_cycle_name(self):
        return self.__cycle_name

    def set_cycle_name(self, cycle_name):
        self.__cycle_name = str(cycle_name)
        return self

    def __get_post_fields(self):
        fields = [
            ('token', self.get_token()),
            ('setup', self.get_setup_name()),
            ('tests_count', self.test_count),
            ('build', self.get_build_name()),
            ('cycle', self.get_cycle_name())
        ]
        return fields

    def post(self, logs_path, print_response=False):
        """
        Uploads log file to logbook site
        :type logs_path: str
        :param logs_path: path to logfs folder
        :type print_response: bool
        :param print_response: If true response will be printed out
        :return: True if success
        """
        if not os.path.isdir(logs_path):
            logging.error("Provided path not exist : [{}].".format(logs_path))
            return False
        upload_file = os.path.join(logs_path, LogBookUploader.__DEF_FILE)
        if not os.path.exists(upload_file):
            logging.error("File for upload not found : [{}].".format(upload_file))
            return False
        files = [('file', os.path.basename(upload_file), upload_file)]
        content_type, body = MultipartFormDataEncoder().encode(self.__get_post_fields(), files)
        headers = {'Content-Type': content_type}
        # For disable proxy
        proxy_handler = urllib2.ProxyHandler({})
        opener = urllib2.build_opener(proxy_handler)

        the_request = urllib2.Request(url=self.url, data=body, headers=headers)
        try:
            self.progress += 1
            self.test_count += 1
            response = opener.open(the_request, timeout=UPLOAD_TIMEOUT)
            # response = urllib2.urlopen(the_request, timeout=UPLOAD_TIMEOUT)
            if print_response:
                LogBookUploader.__print_response(response, "File {}".format(self.test_count))
            logging.info("File {} uploaded.".format(upload_file))
            self.uploads_success += 1
        except urllib2.URLError as ex:
            self.failures_count += 1
            raise Exception("[LogBookUploader:POST]: {}".format(str(ex)))
        except socket.timeout as ex:
            self.timeout_count += 1
            self.failures_count += 1
            raise Exception("[LogBookUploader:POST]: {}".format(str(ex)))
        finally:
            self.progress -= 1
        return True

    def post_async(self, logs_path, print_response=False):
        """
        :type logs_path: str
        :param logs_path: path to logs folder
        :type print_response: bool
        :param print_response: If true response will be printed out
        :return:
        """
        try:
            self.__post_async_waits += 1
            logging.info("Uploading file {} in thread.".format(logs_path))
            async_thread = threading.Thread(target=self.post, args=(logs_path, print_response))
            async_thread.daemon = True

            # wait loop while self.progress > 0
            end_time = int(time.time() + WAIT_TH_MAX_TIME)
            trash_hold = max(1, WAIT_TH_COUNT)
            while self.progress >= trash_hold and time.time() <= end_time:
                time.sleep(0.2)

            if self.progress >= trash_hold and time.time() > end_time:
                logging.info("BREAK FROM WAIT : Current progress {},  adding new one.".format(self.progress))
            async_thread.setName(str(self.test_count + 1))
            async_thread.start()
            self.__threads.append(async_thread)
            while not async_thread.isAlive():
                time.sleep(0.1)
            time.sleep(0.5)
        except Exception as ex:
            logging.exception(str(ex))
            return False
        finally:
            self.__post_async_waits -= 1
        return True

    def wait_for_close(self):
        res = True
        wait_time = 120
        logging.info("Threads opened {}.".format(len(self.__threads)))
        start_wait = time.time()
        if self.progress > 0:
            logging.info("{} thread in progress, wait for {} seconds.".format(self.progress, wait_time))
            while start_wait + wait_time > time.time() and self.progress > 0:
                time.sleep(1)
            if start_wait + wait_time < time.time() and self.progress > 0:
                logging.error("Timeout on wait, in progress {} uploads.".format(self.progress))
                res = False
        for th in self.__threads:
            logging.info("Join thread: {}".format(th.getName()))
            th.join(1)
        return res

    @staticmethod
    def __print_response(response, r_name=''):
        if len(r_name) > 0:
            r_name = "{}: ".format(r_name)
        try:
            for line in response:
                tmp_line = line.replace('\n', ' ').replace('\r', '')
                print("{}{}".format(r_name, tmp_line))
        except Exception:
            pass

    @staticmethod
    def get_random_string(length=50, silent=True):
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
                raise Exception("The min str len should be {}, but {} provided".format(MIN_TOKEN_LEN, length))
        if length > MAX_TOKEN_LEN:
            if silent:
                length = 31
            else:
                raise Exception("The max str len should be {}, but {} provided".format(MAX_TOKEN_LEN, length))
        return ''.join(random.choice(string.ascii_uppercase + string.digits) for _ in range(length))


logging.basicConfig(format='%(asctime)s %(levelname)-5.5s| %(message)s', level=logging.DEBUG)

ws_path = '/home/werd/lbook/_wifi-matfunc-chell'
logbook = LogBookUploader(setup_name="TestPython", cycle_name="CycleNameTest 3")
logbook.post_async(os.path.join(ws_path, 'results-01-network_WiFi_BluetoothStreamPerf.11b/'))
logbook.post_async(os.path.join(ws_path, 'results-02-network_WiFi_BluetoothStreamPerf.11a/'))
logbook.wait_for_close()

logbook.set_build_name("Test Build Name")
# Save LogBookUploader setting
f_name = logbook.save()

# Load  LogBookUploader setting
logbook = LogBookUploader.load(f_name)

logbook.post_async(os.path.join(ws_path, 'results-03-network_WiFi_Perf.ht40/'), True)
logbook.post_async(os.path.join(ws_path, 'results-04-network_WiFi_Perf.11b/'))
logbook.wait_for_close()
