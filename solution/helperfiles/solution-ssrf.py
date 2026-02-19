#!/usr/bin/env python3
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse

HOST = "0.0.0.0"
PORT = 8082
REDIRECTS = 6
FINAL_TARGET = "http://localhost:5000/keys/private"

REDIRECT_307_AT = 3

class RedirectHandler(BaseHTTPRequestHandler):
    def _base_origin(self) -> str:
        """
        Build an absolute origin for redirects using the current request's Host header.
        Falls back to localhost:<PORT> if Host is missing.
        """
        host = self.headers.get("Host") or f"localhost:{PORT}"
        return f"http://{host}"

    def _send_redirect(self, location: str, code: int = 302) -> None:
        self.send_response(code)
        self.send_header("Location", location)
        self.send_header("Content-Type", "text/plain")
        self.end_headers()
        self.wfile.write(f"Redirecting to: {location}\n".encode("utf-8"))

    def do_GET(self):
        origin = self._base_origin()

        if self.path == "/start":
            self._send_redirect(f"{origin}/r/1", 302)
            return

        if self.path.startswith("/r/"):
            try:
                n = int(self.path.split("/r/", 1)[1].split("/", 1)[0])
            except (ValueError, IndexError):
                self.send_error(400, "Bad redirect step")
                return

            code = 307 if n == REDIRECT_307_AT else 302

            if 1 <= n < REDIRECTS:
                self._send_redirect(f"{origin}/r/{n+1}", code)
                return

            if n == REDIRECTS:
                self._send_redirect(FINAL_TARGET, code)
                return

            self.send_error(404, "Redirect step out of range")
            return

        self.send_response(200)
        self.send_header("Content-Type", "text/plain")
        self.end_headers()
        self.wfile.write(b"OK\n")

def main():
    server = HTTPServer((HOST, PORT), RedirectHandler)
    print(f"Redirect test server running on http://localhost:{PORT}")
    print(f"Start chain at: http://localhost:{PORT}/start")
    print(
        f"Redirects: {REDIRECTS} (hop {REDIRECT_307_AT} uses 307), "
        f"final -> {FINAL_TARGET}"
    )
    server.serve_forever()


if __name__ == "__main__":
    main()
