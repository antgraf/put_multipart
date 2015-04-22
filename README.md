# put_multipart
PHP multipart/form-data files receiver for PUT requests.

It allows to receive files in multipart/form-data HTTP format from any stream including php://input for PUT requests. The reason of creating this is the fact that PHP does not populate _FILES superglobal for PUT requests and there is no easy way to get files. This solution is intendent to handle large files so there is no file_get_contents() or preg_split() used. I didn't find good solution among existing ones.
