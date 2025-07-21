#!/usr/bin/env node

/**
 * Chrome Setup Helper for Different Environments
 * Detects and configures Chrome executable for crawling
 */

import fs from 'fs/promises';
import { execSync } from 'child_process';

console.log('🔍 Chrome Setup Helper');
console.log('Platform:', process.platform);
console.log('Architecture:', process.arch);

// Detect Chrome installation
async function detectChrome() {
    const commonPaths = {
        win32: [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            process.env.LOCALAPPDATA + '\\Google\\Chrome\\Application\\chrome.exe',
            process.env.PROGRAMFILES + '\\Google\\Chrome\\Application\\chrome.exe'
        ],
        linux: [
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/snap/bin/chromium',
            '/opt/google/chrome/chrome',
            '/usr/bin/google-chrome-unstable'
        ],
        darwin: [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium'
        ]
    };

    const paths = commonPaths[process.platform] || [];
    const foundPaths = [];

    console.log('\n📋 Checking common Chrome locations...');
    
    for (const chromePath of paths) {
        try {
            await fs.access(chromePath);
            foundPaths.push(chromePath);
            console.log(`✅ Found: ${chromePath}`);
        } catch (e) {
            console.log(`❌ Not found: ${chromePath}`);
        }
    }

    return foundPaths;
}

// Check if Chrome is in PATH
function checkChromeInPath() {
    console.log('\n🔍 Checking Chrome in system PATH...');
    
    const commands = {
        win32: ['where chrome', 'where google-chrome'],
        linux: ['which google-chrome', 'which chromium-browser', 'which chromium'],
        darwin: ['which google-chrome', 'which chromium']
    };

    const cmds = commands[process.platform] || [];
    
    for (const cmd of cmds) {
        try {
            const result = execSync(cmd, { encoding: 'utf8' }).trim();
            if (result) {
                console.log(`✅ Found in PATH: ${result}`);
                return result;
            }
        } catch (e) {
            console.log(`❌ Command failed: ${cmd}`);
        }
    }
    
    return null;
}

// Generate environment setup
function generateEnvSetup(chromePath) {
    console.log('\n📝 Environment Setup:');
    
    if (chromePath) {
        console.log('\n--- For .env file ---');
        console.log(`PUPPETEER_EXECUTABLE_PATH="${chromePath}"`);
        
        console.log('\n--- For command line (Windows) ---');
        console.log(`set PUPPETEER_EXECUTABLE_PATH="${chromePath}"`);
        
        console.log('\n--- For command line (Linux/Mac) ---');
        console.log(`export PUPPETEER_EXECUTABLE_PATH="${chromePath}"`);
        
        console.log('\n--- For Docker ---');
        console.log(`ENV PUPPETEER_EXECUTABLE_PATH="${chromePath}"`);
        
        console.log('\n--- For hosting (cPanel/shared hosting) ---');
        console.log('Add to your hosting control panel environment variables:');
        console.log(`PUPPETEER_EXECUTABLE_PATH = ${chromePath}`);
    } else {
        console.log('❌ No Chrome installation found');
        console.log('\n💡 Installation suggestions:');
        
        if (process.platform === 'linux') {
            console.log('Ubuntu/Debian: sudo apt-get install google-chrome-stable');
            console.log('CentOS/RHEL: sudo yum install google-chrome-stable');
            console.log('Or install Chromium: sudo apt-get install chromium-browser');
        } else if (process.platform === 'win32') {
            console.log('Download from: https://www.google.com/chrome/');
        } else if (process.platform === 'darwin') {
            console.log('Download from: https://www.google.com/chrome/');
            console.log('Or install via Homebrew: brew install --cask google-chrome');
        }
    }
}

// Hosting-specific recommendations
function hostingRecommendations() {
    console.log('\n🌐 Hosting Environment Recommendations:');
    
    console.log('\n--- Shared Hosting ---');
    console.log('• Most shared hosting providers don\'t support Puppeteer');
    console.log('• Consider using a VPS or dedicated server');
    console.log('• Alternative: Use external crawling services');
    
    console.log('\n--- VPS/Cloud Hosting ---');
    console.log('• Install Chrome: apt-get install google-chrome-stable');
    console.log('• Set environment variable in hosting control panel');
    console.log('• Ensure sufficient memory (minimum 1GB RAM)');
    
    console.log('\n--- Docker ---');
    console.log('• Use official Node.js image with Chrome pre-installed');
    console.log('• Example: node:18-slim with Chrome installation');
    console.log('• Add --no-sandbox --disable-setuid-sandbox flags');
    
    console.log('\n--- Heroku ---');
    console.log('• Add buildpack: heroku/nodejs');
    console.log('• Add buildpack: https://github.com/jontewks/puppeteer-heroku-buildpack');
    console.log('• Set PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true');
    
    console.log('\n--- Vercel/Netlify ---');
    console.log('• Use @sparticuz/chromium package');
    console.log('• Serverless functions have size limitations');
    console.log('• Consider external crawling API');
}

// Main execution
async function main() {
    try {
        // Check current environment variable
        if (process.env.PUPPETEER_EXECUTABLE_PATH) {
            console.log(`\n✅ Environment variable already set: ${process.env.PUPPETEER_EXECUTABLE_PATH}`);
        }
        
        // Detect Chrome installations
        const foundPaths = await detectChrome();
        
        // Check PATH
        const pathChrome = checkChromeInPath();
        
        // Determine best Chrome path
        let bestPath = null;
        if (process.env.PUPPETEER_EXECUTABLE_PATH) {
            bestPath = process.env.PUPPETEER_EXECUTABLE_PATH;
        } else if (pathChrome) {
            bestPath = pathChrome;
        } else if (foundPaths.length > 0) {
            bestPath = foundPaths[0]; // Use first found
        }
        
        // Generate setup instructions
        generateEnvSetup(bestPath);
        
        // Hosting recommendations
        hostingRecommendations();
        
        // Test Chrome if found
        if (bestPath) {
            console.log('\n🧪 Testing Chrome executable...');
            try {
                execSync(`"${bestPath}" --version`, { encoding: 'utf8' });
                console.log('✅ Chrome executable is working');
            } catch (e) {
                console.log('❌ Chrome executable test failed:', e.message);
            }
        }
        
    } catch (error) {
        console.error('❌ Setup failed:', error.message);
        process.exit(1);
    }
}

// Run if called directly
if (import.meta.url === `file://${process.argv[1]}`) {
    main();
}

export { detectChrome, checkChromeInPath };
