import string
import pickle


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
