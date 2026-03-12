# Solution for the challenge:

This challenge involves exploitation of two different vulnerabilities, an ssrf and an arbitrary code execution via pip install
- The application is a pypi manager which lets you upload python packages, either by uploading whl files or submitting urls, along with instructions: All packages must contain a signed RECORD file (RECORD.sig). Only packages signed with the repository's private key will be accepted.
- An option to submit urls generally triggers the SSRF neuron for hackers
- Supporting the above point, you could try to submit something likehttp://localhost, but you dont get any output of the execution, making it sort of a blind SSRF.
- Trying to upload a whl file made using e.g. python build gives an error: Signature verification failed: RECORD file not signed with trusted key
- The application also has an install button at the bottom, which when pressed gives the output of a pip install command installing a package named "hello-world-package".

On its own, the application does not give a lot of clues for exploitation
A review of the source code reveals a few things:
- The url submission method is in fact vulnerable to SSRF, but to get the output of the request you made, you have to trigger a specific exception.
- This involves two conditions, if a request does more than 5 redirects, and has a 307 Status Code, then all of the responses in the redirections are returned in the output
- Another folder for a flask application can be found in the source code, which reveals that an internal application is running alongside the original application.
- This flask app is exposing a private key on the /keys/private endpoint
- So by exploiting the SSRF, you can obtain a private key. [1]

How does the private key help?
- Referring to the error previously mentioned, the application is looking for whl files which have a signature of the RECORD file of the package
- This signature can be created using the private key obtained from the previous exploitation. Thus letting users upload whl files successfully

You can upload python packages that the manager stores
- The next thing that can be obtained from the source is the exact pip install command executed by the install button on the frontend. This command is hardcoded to install the package "hello-world-package"
- It has the --index-url flag, pointing to the simple index of our pypi manager
- And the --find-links flag, pointing to a directory /app/local-packages
- The flask app source has a local package folder which is used to create the dist wheel of the original hello-world-package, and store it in /app/local-packages
- This implies the following:
   - On execution of the pip install command, pip looks for the hello-world-package in the simple index given in the --index-url
   - Given it does not find anything at that point, it looks in the /app/local-packages, which is actually where the hello-world package files are located, thus pip installs it. The frontend simply shows the STDOUT of this execution
- All of this can be used to deduce that we can upload a hello-world-package of our own which gets installed by the pip install execution. Which can be achieved by uploading the same package, but with a higher version (anything above 0.1.0 works).


You can now get pip to install your package   
- You may think that by putting malicious code (or cat flag.txt) in the setup.py, building the same as the hello-world-package and uploading it on the manager, would get you the flag. However, pip is very particular about package installation, in that it does not execute anything inside the packages, simply unpacks them
- However, the particular version of pip used here (also highlighted on the frontend) has a vulnerability, which involves the outdated-check.py file
This vulnerability can be used to get Arbitrary Code Execution on the system once pip install runs on a malicious wheel file [2]
- A tldr on the vulnerability
  - The internal self_outdated_check.py is run after the installation of a package, which means a malicious package can overwrite the python file and get malicious code executed (thus the pip --upgrade flag becomes important)
- Thus, we add python code to execute cat on the flag.txt path and put it in pip/_internal/self_outdated_check.py inside our package. Once pip install is executed, the package is unpacked inside /dist-packages/ and overwrites the original self_outdated_check.py, which then gets executed to check if the pip version is the latest one or not, and get the flag printed in the STDOUT.

## References
[1] The SSRF is based on this: https://slcyber.io/research-center/novel-ssrf-technique-involving-http-redirect-loops/
the ctf implementation is not completely faithful to the original vuln
</br>[2] References to the vuln
    - https://github.com/pypa/pip/issues/13079
    - https://security.snyk.io/package/pip/pip/24.1b1


P.S., Some alternative ways to get code exec
   - the self_outdated_check.py method is not the only way to get code execution here
   - Principally speaking python wheel packages when installed by pip, are unpacked. However no files are executed. So, malicious code in package scripts or setup.py wont be executed. So to get malicious code executed, you have to write it into python scripts that actually run after pip install, one such file is ofc self_outdated_check.py which ran unsafely in pip version 24.1b1
   - An alternative file that executes is sitecustomize.py, which was found by a participant to execute malicious python code, i.e. get the flag.
   - I believe this may not be the only way to get code execution here, and am presuming you guys may have found more ways
   - You can upload the same here as well