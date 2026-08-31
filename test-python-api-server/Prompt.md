Private URL for my reference - https://aistudio.google.com/prompts/1_ZrIAY1BrTVWIXaY1fcKVK9RLF7o8R-8

Prompts to generate the python code to run as a mock-server -

_Can you write a basic python or php testing script which will run on localhost and act as the api server? The example data given in the pdf document can be used._

later, 

_Does this php mock server also support the single-sign-on? If yes, please give me detailed steps on how to enable single-sign-on method in Moodle, and how to test using this mock server._

This generated the python mock server. To use it, we would need python installed, and these modules - 
```
pip install flask pyjwt cryptography
```
or in this case of an Ubuntu server, 
```
sudo apt install python3-flask python3-jwt python3-cryptography
```

_I get this message when creating the custom service on Moodle - "For security reasons only https connections are allowed, sorry."_

_So I guess I would need to set up an Apache virtual server with reverse proxy to port 8000? Or any other way?_

Then Gemini suggested using `ngrok http 8000` to test it.
