import json
import ssl
import ast
import time
import urllib.request
import logging


LOGBOOK_DOMAIN = "logbook.domain.com"
LOGBOOK_URL = f'http://{LOGBOOK_DOMAIN}'


class LogbookAPI(object):
    def __init__(self, verbose=False):
        self._logbook_url = LOGBOOK_URL  # type: str
        self.verbose = verbose  # type: bool
        self.ex_msg = 'Failed for request {req}, with response: {res}, with exit code: {ex_code}'

    def get_lbk_url(self):
        # type: () -> str
        return self._logbook_url

    def urlopen_lbk(self, url, method, data=None, headers=None, verbose=True, sleep_on_error=30, timeout=120):
        # type: (str, str, dict, dict, bool, int, int) -> dict
        req_func = urllib.request.Request
        url_func = urllib.request.urlopen

        try:
            expired_timeout = timeout
            url = f'{self.get_lbk_url()}/{url}'
            try:
                if headers:
                    _r = req_func(url=url, headers=headers)
                else:
                    _r = req_func(url=url)
            except Exception as ex:
                raise Exception(self.ex_msg.format(req=url, res=ex, ex_code=''))

            _r.get_method = lambda: method
            msg = f'{_r.get_method()} \t logbook url : {url} \t {data}'
            if verbose:
                logging.info(msg)
            gcontext = ssl._create_unverified_context()
            try:
                if data:
                    data = json.dumps(data)  # type: str
                    data = data.encode("utf-8")  # type: bytes
                    response = url_func(_r, data=data, context=gcontext, timeout=expired_timeout)
                else:
                    response = url_func(_r, context=gcontext, timeout=expired_timeout)
            except Exception as ex:
                raise Exception(self.ex_msg.format(req=url, res=ex, ex_code=''))

            res_data = response.read()
            content = json.loads(res_data)
        except Exception as ex:
            if verbose:
                logging.exception(ex)
            else:
                logging.error(ex)
            time.sleep(sleep_on_error)
            raise Exception(self.ex_msg.format(req=url, res='', ex_code=ex))
        return content

    @staticmethod
    def convert_data(data):
        # type: (str or dict) -> dict
        new_data = dict()
        try:
            new_data = ast.literal_eval(data)
        except:
            try:
                new_data = json.loads(data)
            except:
                return data
        return new_data

    def get_new_exec(self, state=None):
        # type: (int) -> dict
        res_data = None
        lbk_url = "api/execution/publisher/count"
        if state:
            lbk_url = f'{lbk_url}/{state}'
        try:
            res_data = self.urlopen_lbk(lbk_url, 'GET')
            res_data = self.convert_data(res_data)
        except Exception as ex:
            logging.error(f'[LBK] Error at get count executions from logbook, {ex}')
            logging.exception(ex)
        return res_data

    def get_execution_from_lbk(self, execution_key):
        # type: (str) -> dict
        exe_data = None
        try:
            lbk_url = f"cycle/te/{execution_key}"
            exe_data = self.urlopen_lbk(lbk_url, 'GET')
            exe_data = self.convert_data(exe_data)
        except Exception as ex:
            logging.error(f'[LBK] Error at get from logbook, {ex}')
        return exe_data

    def get_new_exe_lbk(self, state=None):
        # type: (int) -> dict
        """
        Get [Logbook] - suite with publisher=1, state=0 and JiraKey=None ( => state: 1)
        :return: UUID - str, ID - int
        """
        if not state:
            state = 0
        lbk_url = f'api/execution/publisher/state/{state}'
        exe_data = self.urlopen_lbk(lbk_url, 'GET')
        if 'message' in exe_data:
            if 'Suites not found' in exe_data['message']:
                logging.debug(f'Suite not found for : {lbk_url}')
                return None
        return exe_data

    def update_test_execution_lbk(self, test_execution_key, test_set_url, exe_id):
        # type: (str, str, str) -> dict
        """
        Post [Logbook] - update the test-execution key and test-set URL ( => state: 2)
        :param test_execution_key: Execution Key
        :param test_set_url: Test Set jira URL
        :param exe_id: Suite Execution lbk ID
        :return:
        """
        lbk_url = f"api/execution/publisher/move_to_2/{exe_id}"
        headers = {'Content-Type': 'application/json'}
        exe_data = None
        req_data = self.get_fields_for_exec(test_execution_key, test_set_url)
        try:
            exe_data = self.urlopen_lbk(lbk_url, 'POST', data=req_data, headers=headers)
        except Exception as ex:
            logging.exception(f'[LBK] Error at update execution and move to 2, {ex}')
            raise
        return exe_data

    @staticmethod
    def get_fields_for_exec(execution_key, set_url):
        fields = dict()
        fields['test_execution_key'] = str(execution_key)
        fields['test_set_url'] = str(set_url)
        return fields

    def get_tests_by_exec_id_lbk(self, exec_id, verbose=True, sleep_on_error=30):
        # type: (int, bool, int) -> dict
        """
        Get [Logbook] - suite with publisher=1, state=0 and JiraKey=None ( => state: 1)
        :return: UUID - str, ID - int
        """
        data = None
        try:
            lbk_url = f'api/execution/publisher/move_to_3/{exec_id}'
            data = self.urlopen_lbk(lbk_url, 'POST', verbose=verbose, sleep_on_error=sleep_on_error)
        except Exception as ex:
            logging.error(f'[LBK] Error at get tests and move to 3, {ex}')
        return data

    def update_test(self, test_id, test_key, exec_key, verbose=True):
        # type: (int, str, str, bool) -> dict
        lbk_url = f"test/update/{test_id}"
        headers = {'Content-Type': 'application/json'}
        res_data = None
        req_data = self.get_fields_for_tests(test_key, exec_key)
        try:
            res_data = self.urlopen_lbk(lbk_url, 'POST', data=req_data, headers=headers, verbose=verbose)
        except Exception as ex:
            logging.error(f'[LBK] Error at update test, {ex}')
        return res_data

    def move_to_4(self, exec_id):
        # type: (int) -> dict
        data = None
        lbk_url = f'api/execution/publisher/move_to_4/{exec_id}'
        headers = {'Content-Type': 'application/json'}
        req_data = {'message': f'Updating the tests of {exec_id} has been completed successfully, by Publisher'}
        try:
            data = self.urlopen_lbk(lbk_url, 'POST', data=req_data, headers=headers)
        except Exception as ex:
            logging.error(f'[LBK] Error at move to 4, {ex}')
        logging.debug(f'{data}')
        return data

    @staticmethod
    def get_fields_for_tests(test_key, exec_key):
        fields = dict()
        fields['test_key'] = str(test_key)
        fields['test_execution_key'] = str(exec_key)
        return fields
