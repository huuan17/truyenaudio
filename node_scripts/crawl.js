#!/usr/bin/env node

import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';
import fs from 'fs/promises';
import path from 'path';

puppeteer.use(StealthPlugin());

// Enhanced error handling and logging
const LOG_LEVELS = {
  ERROR: 'ERROR',
  WARN: 'WARN',
  INFO: 'INFO',
  DEBUG: 'DEBUG'
};

function logMessage(level, message, data = null) {
  const timestamp = new Date().toISOString();
  const logEntry = {
    timestamp,
    level,
    message,
    data,
    pid: process.pid
  };

  // Console output
  const prefix = level === LOG_LEVELS.ERROR ? '❌' :
                level === LOG_LEVELS.WARN ? '⚠️' :
                level === LOG_LEVELS.INFO ? '✅' : '🔍';
  console.log(`${prefix} [${timestamp}] ${message}`);

  // Detailed data if provided
  if (data) {
    console.log('   Data:', JSON.stringify(data, null, 2));
  }

  return logEntry;
}

// Nhận tham số từ command line
const [, , baseUrl, start, end, outputFolder, singleMode] = process.argv;

if (!baseUrl || !start || !end || !outputFolder) {
  logMessage(LOG_LEVELS.ERROR, 'Thiếu tham số. Cú pháp: node crawl.js <baseUrl> <startChapter> <endChapter> <outputFolder> [singleMode]');
  process.exit(1);
}

const START_CHAPTER = parseInt(start);
const END_CHAPTER = parseInt(end);
const SINGLE_MODE = singleMode === '1'; // Chế độ crawl một chương duy nhất

// Validate parameters
if (isNaN(START_CHAPTER) || isNaN(END_CHAPTER)) {
  logMessage(LOG_LEVELS.ERROR, 'Chapter numbers must be valid integers', {
    start: start,
    end: end,
    parsedStart: START_CHAPTER,
    parsedEnd: END_CHAPTER
  });
  process.exit(1);
}

if (START_CHAPTER > END_CHAPTER) {
  logMessage(LOG_LEVELS.ERROR, 'Start chapter cannot be greater than end chapter', {
    start: START_CHAPTER,
    end: END_CHAPTER
  });
  process.exit(1);
}

(async () => {
  const startTime = Date.now();
  let browser = null;
  let totalErrors = 0;
  let totalSuccess = 0;

  try {
    // Log crawl start
    logMessage(LOG_LEVELS.INFO, 'Starting crawl process', {
      baseUrl,
      startChapter: START_CHAPTER,
      endChapter: END_CHAPTER,
      outputFolder,
      singleMode: SINGLE_MODE,
      totalChapters: END_CHAPTER - START_CHAPTER + 1
    });

    // Tạo thư mục nếu chưa tồn tại
    try {
      await fs.mkdir(outputFolder, { recursive: true });
      logMessage(LOG_LEVELS.INFO, `Output directory created/verified: ${outputFolder}`);
    } catch (e) {
      logMessage(LOG_LEVELS.ERROR, 'Failed to create output directory', {
        outputFolder,
        error: e.message,
        stack: e.stack
      });
      process.exit(1);
    }

    // Chrome executable detection with fallback
    async function findChromeExecutable() {
      logMessage(LOG_LEVELS.DEBUG, 'Detecting Chrome executable');

      // Priority 1: Environment variable
      if (process.env.PUPPETEER_EXECUTABLE_PATH) {
        logMessage(LOG_LEVELS.INFO, 'Using Chrome from environment variable', {
          path: process.env.PUPPETEER_EXECUTABLE_PATH
        });
        return process.env.PUPPETEER_EXECUTABLE_PATH;
      }

      // Priority 2: Let Puppeteer auto-detect (works on most hosting)
      try {
        const testBrowser = await puppeteer.launch({ headless: 'new' });
        await testBrowser.close();
        logMessage(LOG_LEVELS.INFO, 'Puppeteer auto-detection successful');
        return undefined; // Let Puppeteer handle it
      } catch (e) {
        logMessage(LOG_LEVELS.WARN, 'Puppeteer auto-detection failed, trying manual paths', {
          error: e.message
        });
      }

      // Priority 3: Common system paths
  const commonPaths = {
    win32: [
      'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
      'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
      process.env.LOCALAPPDATA + '\\Google\\Chrome\\Application\\chrome.exe'
    ],
    linux: [
      '/usr/bin/google-chrome',
      '/usr/bin/google-chrome-stable',
      '/usr/bin/chromium-browser',
      '/usr/bin/chromium',
      '/snap/bin/chromium'
    ],
    darwin: [
      '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
      '/Applications/Chromium.app/Contents/MacOS/Chromium'
    ]
  };

  const paths = commonPaths[process.platform] || [];

      for (const chromePath of paths) {
        try {
          await fs.access(chromePath);
          logMessage(LOG_LEVELS.INFO, `Found Chrome at: ${chromePath}`);
          return chromePath;
        } catch (e) {
          // Continue to next path
        }
      }

      throw new Error('Chrome executable not found. Please install Chrome or set PUPPETEER_EXECUTABLE_PATH environment variable.');
    }

    // Launch browser with smart Chrome detection and stability improvements
    try {
      const executablePath = await findChromeExecutable();
      browser = await puppeteer.launch({
        headless: 'new',
        executablePath,
        args: [
          '--no-sandbox',
          '--disable-setuid-sandbox',
          '--disable-dev-shm-usage',
          '--disable-gpu',
          '--no-first-run',
          '--no-zygote',
          '--disable-web-security',
          '--disable-features=VizDisplayCompositor',
          '--disable-background-timer-throttling',
          '--disable-backgrounding-occluded-windows',
          '--disable-renderer-backgrounding',
          '--disable-ipc-flooding-protection',
          '--window-size=1920,1080'
        ],
        defaultViewport: { width: 1920, height: 1080 },
        timeout: 60000
      });
      logMessage(LOG_LEVELS.INFO, 'Browser launched successfully');
    } catch (e) {
      logMessage(LOG_LEVELS.ERROR, 'Failed to launch browser', {
        error: e.message,
        stack: e.stack,
        suggestion: 'Try setting PUPPETEER_EXECUTABLE_PATH environment variable'
      });
      process.exit(1);
    }

    // Create page with enhanced settings
    const page = await browser.newPage();

    // Set user agent and other headers to avoid blocking
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'en-US,en;q=0.9,vi;q=0.8',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
    });

    // Set viewport
    await page.setViewport({ width: 1920, height: 1080 });

    // Enable request interception for better control
    await page.setRequestInterception(true);
    page.on('request', (req) => {
      // Block unnecessary resources to speed up loading
      if (req.resourceType() === 'image' || req.resourceType() === 'stylesheet' || req.resourceType() === 'font') {
        req.abort();
      } else {
        req.continue();
      }
    });

    logMessage(LOG_LEVELS.INFO, `Starting chapter crawl from ${START_CHAPTER} to ${END_CHAPTER}`, {
      totalChapters: END_CHAPTER - START_CHAPTER + 1,
      singleMode: SINGLE_MODE
    });

    for (let chapter = START_CHAPTER; chapter <= END_CHAPTER; chapter++) {
      const url = `${baseUrl}${chapter}.html`;
      const chapterStartTime = Date.now();
      let retryCount = 0;
      const maxRetries = 3;
      let success = false;

      while (!success && retryCount < maxRetries) {
        try {
          logMessage(LOG_LEVELS.DEBUG, `Accessing chapter ${chapter} (attempt ${retryCount + 1}/${maxRetries})`, { url });

          // Create new page for each chapter to avoid frame detachment issues
          if (retryCount > 0) {
            try {
              await page.close();
            } catch (e) {
              // Ignore close errors
            }

            // Create fresh page
            const newPage = await browser.newPage();
            await newPage.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            await newPage.setViewport({ width: 1920, height: 1080 });

            // Set up request interception for new page
            await newPage.setRequestInterception(true);
            newPage.on('request', (req) => {
              if (req.resourceType() === 'image' || req.resourceType() === 'stylesheet' || req.resourceType() === 'font') {
                req.abort();
              } else {
                req.continue();
              }
            });

            // Replace the page reference
            page = newPage;
          }

          // Navigate to page with timeout and retry logic
          await page.goto(url, {
            waitUntil: 'domcontentloaded',
            timeout: 30000
          });

          // Wait for content to load with multiple selectors
          try {
            await page.waitForSelector('div.chapter-c', { timeout: 10000 });
          } catch (e) {
            // Try alternative selectors
            const alternativeSelectors = ['.chapter-content', '.content', '#content', '.story-content'];
            let selectorFound = false;

            for (const selector of alternativeSelectors) {
              try {
                await page.waitForSelector(selector, { timeout: 5000 });
                selectorFound = true;
                break;
              } catch (e) {
                // Continue to next selector
              }
            }

            if (!selectorFound) {
              throw new Error('Content selector not found');
            }
          }

          // Extract content with fallback selectors
          let content = null;
          const contentSelectors = ['div.chapter-c', '.chapter-content', '.content', '#content', '.story-content'];

          for (const selector of contentSelectors) {
            try {
              content = await page.$eval(selector, el => el.innerText);
              if (content && content.trim().length > 0) {
                break;
              }
            } catch (e) {
              // Continue to next selector
            }
          }

          if (!content || content.trim().length === 0) {
            throw new Error('Empty content extracted from all selectors');
          }

          // Save to file (directory should exist, created by Laravel)
          const filename = path.join(outputFolder, `chuong-${chapter}.txt`);

          // Check if file exists
          let fileExists = false;
          try {
            await fs.access(filename);
            fileExists = true;
            logMessage(LOG_LEVELS.DEBUG, `File exists, updating content for chapter ${chapter}`, { filename });
          } catch (e) {
            logMessage(LOG_LEVELS.DEBUG, `File doesn't exist, creating new file for chapter ${chapter}`, { filename });
          }

          // Write content to file
          await fs.writeFile(filename, content, 'utf-8');

          // Log file creation/update
          const fileSize = (await fs.stat(filename)).size;
          logMessage(LOG_LEVELS.DEBUG, `File ${fileExists ? 'updated' : 'created'} successfully`, {
            filename,
            fileSize,
            action: fileExists ? 'update' : 'create'
          });

          const chapterTime = Date.now() - chapterStartTime;
          totalSuccess++;
          success = true;

          logMessage(LOG_LEVELS.INFO, `Successfully saved chapter ${chapter}`, {
            filename,
            contentLength: content.length,
            processingTime: chapterTime + 'ms',
            attempts: retryCount + 1
          });

        } catch (e) {
          retryCount++;
          const chapterTime = Date.now() - chapterStartTime;

          logMessage(LOG_LEVELS.WARN, `Failed to crawl chapter ${chapter} (attempt ${retryCount}/${maxRetries})`, {
            url,
            error: e.message,
            stack: e.stack,
            processingTime: chapterTime + 'ms',
            retryCount,
            maxRetries
          });

          if (retryCount >= maxRetries) {
            totalErrors++;

            logMessage(LOG_LEVELS.ERROR, `Failed to crawl chapter ${chapter} after ${maxRetries} attempts`, {
              url,
              error: e.message,
              finalAttempt: true,
              totalErrors,
              totalSuccess
            });

            if (SINGLE_MODE) {
              await browser.close();
              process.exit(1);
            }

            break; // Exit retry loop
          } else {
            // Add delay before retry to avoid rate limiting
            const delay = Math.min(2000 * retryCount, 10000); // Exponential backoff, max 10s
            logMessage(LOG_LEVELS.INFO, `Retrying chapter ${chapter} in ${delay}ms...`);
            await new Promise(resolve => setTimeout(resolve, delay));
          }
        }
      }

      // Add delay between chapters to be respectful to the server (2 seconds per chapter)
      if (chapter < END_CHAPTER) {
        await new Promise(resolve => setTimeout(resolve, 2000));
      }
    }

  } catch (globalError) {
    logMessage(LOG_LEVELS.ERROR, 'Global crawl error', {
      error: globalError.message,
      stack: globalError.stack,
      totalErrors,
      totalSuccess
    });
  } finally {
    // Cleanup
    if (browser) {
      try {
        await browser.close();
        logMessage(LOG_LEVELS.INFO, 'Browser closed successfully');
      } catch (e) {
        logMessage(LOG_LEVELS.WARN, 'Error closing browser', { error: e.message });
      }
    }

    const totalTime = Date.now() - startTime;
    const totalChapters = END_CHAPTER - START_CHAPTER + 1;

    logMessage(LOG_LEVELS.INFO, 'Crawl process completed', {
      totalChapters,
      successCount: totalSuccess,
      errorCount: totalErrors,
      successRate: totalChapters > 0 ? ((totalSuccess / totalChapters) * 100).toFixed(1) + '%' : '0%',
      totalTime: totalTime + 'ms',
      avgTimePerChapter: totalSuccess > 0 ? Math.round(totalTime / totalSuccess) + 'ms' : 'N/A'
    });

    // Exit with appropriate code
    process.exit(totalErrors > 0 ? 1 : 0);
  }
})();
