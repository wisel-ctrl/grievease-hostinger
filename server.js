const https = require('https');
const fs = require('fs');
const express = require('express'); // Make sure to install Express first

const app = express();

// Replace these paths if you moved the .pem files
const options = {
  key: fs.readFileSync('localhost-key.pem'),  // Private key
  cert: fs.readFileSync('localhost.pem')      // Certificate
};

// Serve your PHP files (adjust public directory if needed)
app.use(express.static('public')); 

// Start HTTPS server
https.createServer(options, app).listen(443, () => {
  console.log('HTTPS running on https://localhost');
});