#!/usr/bin/env node

/**
 * Production-ready crawl script with hosting compatibility
 * Handles Chrome detection and fallbacks for different hosting environments
 */

import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs/promises';
import path from 'path';

puppeteer.use(StealthPlugin());

// Configuration for different hosting environments
const HOSTING_CONFIGS = {
    // Shared hosting (limited support)
    shared: {
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--no-first-run',
            '--no-zygote',
            '--single-process',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding'
        ],
        timeout: 60000
    },
    
    // VPS/Cloud hosting
    vps: {
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ],
        timeout: 30000
    },
    
    // Docker container
    docker: {
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--no-first-run',
            '--no-zygote',
            '--single-process'
        ],
        timeout: 45000
    },
    
    // Local development
    local: {
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        timeout: 30000
    }
};

// Detect hosting environment
function detectEnvironment() {
    // Check for common hosting environment variables
    if (process.env.HEROKU || process.env.DYNO) return 'heroku';
    if (process.env.VERCEL || process.env.VERCEL_ENV) return 'vercel';
    if (process.env.NETLIFY || process.env.NETLIFY_BUILD_BASE) return 'netlify';
    if (process.env.DOCKER || fs.existsSync('/.dockerenv')) return 'docker';
    if (process.env.CPANEL || process.env.SHARED_HOSTING) return 'shared';
    if (process.env.VPS || process.env.CLOUD_HOSTING) return 'vps';
    
    return 'local';
}

// Smart Chrome detection with hosting-specific fallbacks
async function findChromeExecutable() {
    const environment = detectEnvironment();
    console.log(`🌐 Detected environment: ${environment}`);
    
    // Priority 1: Environment variable
    if (process.env.PUPPETEER_EXECUTABLE_PATH) {
        console.log(`✅ Using environment variable: ${process.env.PUPPETEER_EXECUTABLE_PATH}`);
        return process.env.PUPPETEER_EXECUTABLE_PATH;
    }
    
    // Priority 2: Hosting-specific paths
    const hostingPaths = {
        heroku: ['/app/.apt/usr/bin/google-chrome-stable'],
        vercel: [], // Vercel uses @sparticuz/chromium
        netlify: [], // Netlify has limitations
        docker: ['/usr/bin/google-chrome', '/usr/bin/chromium'],
        shared: [], // Most shared hosting doesn't support
        vps: ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium-browser'],
        local: []
    };
    
    const specificPaths = hostingPaths[environment] || [];
    for (const chromePath of specificPaths) {
        try {
            await fs.access(chromePath);
            console.log(`✅ Found hosting-specific Chrome: ${chromePath}`);
            return chromePath;
        } catch (e) {
            // Continue to next path
        }
    }
    
    // Priority 3: Let Puppeteer auto-detect
    try {
        console.log('🔍 Trying Puppeteer auto-detection...');
        return undefined; // Let Puppeteer handle it
    } catch (e) {
        console.log('⚠️ Puppeteer auto-detection may fail, preparing fallbacks...');
    }
    
    // Priority 4: Common system paths
    const commonPaths = {
        win32: [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
        ],
        linux: [
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/snap/bin/chromium'
        ],
        darwin: [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
        ]
    };
    
    const paths = commonPaths[process.platform] || [];
    for (const chromePath of paths) {
        try {
            await fs.access(chromePath);
            console.log(`✅ Found system Chrome: ${chromePath}`);
            return chromePath;
        } catch (e) {
            // Continue to next path
        }
    }
    
    // If we reach here, Chrome is not found
    throw new Error(`Chrome not found for ${environment} environment. Please install Chrome or set PUPPETEER_EXECUTABLE_PATH.`);
}

// Launch browser with environment-specific configuration
async function launchBrowser() {
    const environment = detectEnvironment();
    const config = HOSTING_CONFIGS[environment] || HOSTING_CONFIGS.local;
    
    try {
        const executablePath = await findChromeExecutable();
        
        const launchOptions = {
            headless: 'new',
            executablePath,
            args: config.args,
            timeout: config.timeout
        };
        
        console.log(`🚀 Launching browser for ${environment} environment...`);
        const browser = await puppeteer.launch(launchOptions);
        console.log('✅ Browser launched successfully');
        
        return browser;
    } catch (error) {
        console.error('❌ Failed to launch browser:', error.message);
        
        // Provide environment-specific help
        if (environment === 'shared') {
            console.error('💡 Shared hosting often doesn\'t support Puppeteer. Consider using a VPS.');
        } else if (environment === 'heroku') {
            console.error('💡 For Heroku, add the puppeteer buildpack and set PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true');
        } else if (environment === 'docker') {
            console.error('💡 For Docker, install Chrome in your Dockerfile: apt-get install google-chrome-stable');
        }
        
        throw error;
    }
}

// Main crawl function with improved error handling
async function crawlStory(baseUrl, startChapter, endChapter, outputFolder, singleMode = false) {
    // Validate parameters
    if (!baseUrl || !startChapter || !endChapter || !outputFolder) {
        throw new Error('Missing required parameters: baseUrl, startChapter, endChapter, outputFolder');
    }
    
    const START_CHAPTER = parseInt(startChapter);
    const END_CHAPTER = parseInt(endChapter);
    const SINGLE_MODE = singleMode === '1' || singleMode === true;
    
    // Create output directory
    try {
        await fs.mkdir(outputFolder, { recursive: true });
        console.log(`✅ Output directory ready: ${outputFolder}`);
    } catch (e) {
        throw new Error(`Failed to create output directory: ${e.message}`);
    }
    
    // Launch browser
    const browser = await launchBrowser();
    const page = await browser.newPage();
    
    // Set page timeout based on environment
    const environment = detectEnvironment();
    const config = HOSTING_CONFIGS[environment] || HOSTING_CONFIGS.local;
    page.setDefaultTimeout(config.timeout);
    
    if (!SINGLE_MODE) {
        console.log(`🔍 Crawling chapters ${START_CHAPTER} to ${END_CHAPTER}`);
    }
    
    let successCount = 0;
    let errorCount = 0;
    
    try {
        for (let chapter = START_CHAPTER; chapter <= END_CHAPTER; chapter++) {
            const url = `${baseUrl}${chapter}.html`;
            
            if (!SINGLE_MODE) {
                console.log(`📖 Processing chapter ${chapter}...`);
            }
            
            try {
                await page.goto(url, { 
                    waitUntil: 'domcontentloaded', 
                    timeout: config.timeout 
                });
                
                const content = await page.$eval('div.chapter-c', el => el.innerText);
                
                if (!content || content.trim().length === 0) {
                    throw new Error('Empty content extracted');
                }
                
                const filename = path.join(outputFolder, `chuong-${chapter}.txt`);
                await fs.writeFile(filename, content, 'utf-8');
                
                successCount++;
                
                if (!SINGLE_MODE) {
                    console.log(`✅ Saved chapter ${chapter} (${content.length} chars)`);
                }
                
                // Small delay to be respectful to the server
                await new Promise(resolve => setTimeout(resolve, 1000));
                
            } catch (e) {
                errorCount++;
                console.error(`❌ Error in chapter ${chapter}: ${e.message}`);
                
                if (SINGLE_MODE) {
                    throw e; // Exit immediately in single mode
                }
                
                // Continue with next chapter in batch mode
            }
        }
    } finally {
        await browser.close();
    }
    
    if (!SINGLE_MODE) {
        console.log(`🎉 Crawling completed: ${successCount} success, ${errorCount} errors`);
    }
    
    return { successCount, errorCount };
}

// CLI interface
if (import.meta.url === `file://${process.argv[1]}`) {
    const [, , baseUrl, start, end, outputFolder, singleMode] = process.argv;
    
    if (!baseUrl || !start || !end || !outputFolder) {
        console.error('❌ Missing parameters. Usage: node crawl-production.js <baseUrl> <startChapter> <endChapter> <outputFolder> [singleMode]');
        console.error('Example: node crawl-production.js https://example.com/chapter- 1 10 ./output');
        process.exit(1);
    }
    
    crawlStory(baseUrl, start, end, outputFolder, singleMode)
        .then(result => {
            if (result.errorCount > 0) {
                process.exit(1);
            }
        })
        .catch(error => {
            console.error('❌ Crawl failed:', error.message);
            process.exit(1);
        });
}

export { crawlStory, detectEnvironment, findChromeExecutable };
