To run the python test server, we need 
```
pip install flask pyjwt cryptography
```
or in my case on Ubuntu,
```
sudo apt install python3-flask python3-jwt python3-cryptography
```

Please see [Prompt.md](https://github.com/hn-88/swayamplus-sso-api-integration/blob/main/test-python-api-server/Prompt.md) for the prompts used to create this mock server used for testing.

Ngrok will output a Forwarding URL that looks like this: https://a1b2-c3d4.ngrok-free.app

In Moodle's OAuth 2 settings, use https://a1b2-c3d4.ngrok-free.app/oidc as the Service base URL.

Also, in `launch.php` of the local plugin in this repo,
```
// CHANGE THIS:
// if ($iss !== 'https://swayamplus.education.gov.in/oidc') {

// TO THIS (for testing):
if ($iss !== 'https://a1b2-c3d4.ngrok-free.app/oidc') {
```
We also need to change the `$issuerid` in `launch.php` to match the id - adding a settings.php for this.
