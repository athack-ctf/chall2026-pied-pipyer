# How to Solve the Challenge?

Considerations expected
- The website says its a pypi manager, with two different ways to upload whl files
- There is also an install button, which when clicked shows the output of a pip install command
- The code's analysis shows three things
    - The server also has a flask application that is not exposed, and it gives a private key on the endpoint /keys/private, (Indicating an SSRF vector)
    - The url method of submitting the file has a weird way of doing redirects, in which one specific condition leads to the request data being exposed
    - The pip install runs index url pointing to the pypi manager first, so if a package with a higher version is on this manager, then it would be installed
    
- The pip version installed is also revealed to be 24.1b1, on doing some research (e.g. search pip 24.1b1 code execution), it reveals the lazy import way of exploiting the code execution

## Steps to Solve

- Step 1: Create a python script that does the redirection attack as described in the novel ssrf reference [2]. Performing this attack on the `http://localhost:5000/keys/private` endpoint should give them the private key. The file `solution-ssrf.py` contained in the helper files should prove a reference on how to do this.

- Step 2: The private key can be used with openssl to sign the RECORD file and repack into the whl file. This is based on PEP 427, but uses RECORD.sig instead of jws. The page explicitly states this as well.

- Step 3: Uploading this file should succeed, and allow you to store your own whl files on the manager. 

- Step 4: The goal from here is to get code execution from `pip install` installing your package*. The expected way is to use [1] to create a malicious self_outdated_check.py, pack it properly, create the sig file and repack to get the malicious whl file

- Step 5: Make sure the name and schema of the file matches exactly the name of the package being installed by pip. Given that is the case, uploading the file, and then clicking on install should get the code executed.

References:

[1] https://github.com/pypa/pip/issues/13079

[2] https://slcyber.io/research-center/novel-ssrf-technique-involving-http-redirect-loops/
