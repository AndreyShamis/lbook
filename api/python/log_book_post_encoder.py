import sys
import uuid
import codecs
import io
import mimetypes


class LogBookPostEncoder:

    def __init__(self):
        self.frontier = uuid.uuid4().hex
        self.content_type = f'multipart/form-data; boundary={self.frontier}'

    def encode(self, fields: list, files: list):
        tmp_body = io.BytesIO()
        for part in self.iterate_on_fields(fields, files):
            tmp_body.write(part[0])
        return self.content_type, tmp_body.getvalue()

    @classmethod
    def convert(cls, s):
        if isinstance(s, bytes) and sys.hexversion >= 0x03000000:
            s = s.decode('utf-8')
        return s

    def iterate_on_fields(self, fields: list, files: list):
        nl = '\r\n'
        encode_func = codecs.getencoder('utf-8')
        for src_key, value in fields:
            key = self.convert(src_key)
            yield encode_func(f'--{self.frontier}{nl}')
            yield encode_func(self.convert(f'Content-Disposition: form-data; name="{key}"{nl}'))
            yield encode_func(nl)
            if isinstance(value, int) or isinstance(value, float):
                value = str(value)
            yield encode_func(self.convert(value))
            yield encode_func(nl)
        for src_key, src_file_name, file_path in files:
            file_name = self.convert(src_file_name)
            key = self.convert(src_key)
            yield encode_func(f'--{self.frontier}{nl}')
            yield encode_func(self.convert(f'Content-Disposition: form-data; name="{key}"; filename="{file_name}"{nl}'))
            content_type = mimetypes.guess_type(file_name)[0] or 'application/octet-stream'
            yield encode_func(f'Content-Type: {content_type}{nl}')
            yield encode_func(nl)
            with open(file_path, 'rb') as fd:
                buff = fd.read()
                yield buff, len(buff)
            yield encode_func(nl)
        yield encode_func(f'--{self.frontier}--{nl}')
