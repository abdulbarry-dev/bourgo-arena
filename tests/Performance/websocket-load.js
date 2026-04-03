import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Load .env variables manually for ease in testing
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const envPath = path.resolve(__dirname, '../../.env');
const envObj = {};
if (fs.existsSync(envPath)) {
    const envContent = fs.readFileSync(envPath, 'utf8');
    envContent.split('\n').forEach(line => {
        const [key, ...values] = line.split('=');
        if (key && values.length > 0) envObj[key.trim()] = values.join('=').replace(/^"|"$/g, '').trim();
    });
} // else ignore

const APP_KEY = envObj['REVERB_APP_KEY'] || 'fallback';
const HOST = envObj['REVERB_HOST'] || '127.0.0.1';
const PORT = envObj['REVERB_PORT'] || 8080;
const APP_URL = envObj['APP_URL'] || 'http://127.0.0.1:8000';

const CLIENT_COUNT = 50;
const clients = [];
let receivedCount = 0;
let broadcastStartTime = 0;

console.log(`Starting load test. Connecting to Reverb ws://${HOST}:${PORT} AppKey: ${APP_KEY}`);
console.log(`Will spawn ${CLIENT_COUNT} clients...`);

// Just setting up the script so it can be run manually instead of executing it fully automatically here
// since it requires the server and Reverb to be running simultaneously.

// In a real load test environment, we would use K6, Artillery or Locust
