# Kitten Proxy

Kitten Proxy is another web based proxy script developed with PHP and CURL library.
This is still a very early version, but it should works fine.

### Feature

- Using Base64 url to make sure the URL safe from any website filters.
- Cookies and session are maintained by the CURL.

### Requirements

- Any modern PHP version with CURL extension.
- I use nginx, but any web server should works if you can adjust the rewrite rule to match.

### Installation

1. Copy the entire folder contents to your document root.
2. Configure the Nginx rewrite rule 
```
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
```

That's all. Access it from your browser to use the proxy.

### Configuration

At the moment its doesn't need any configuration, just run it. In the future, there is a possibility for customisation.
