 My PHP API Gateway

 Setup
- Requires Apache (with mod_rewrite) and PHP.
- Place the `my_api_gateway` folder in your web root.
- Make sure `logs/` and `ratelimit_data/` folders are writable.

 API Keys
- `key123` - UserA
- `key456` - UserB

 Testing with curl

 Successful
```bash
curl -H "X-API-Key: key123" http://localhost/my_api_gateway/api/users
