const puppeteer = require('puppeteer');
const fs = require('fs').promises;

console.log('🧪 Testing simple crawl with CommonJS...');

const testUrl = 'https://truyencom.com/vo-thuong-sat-than/chuong-1.html';
console.log(`📖 Testing URL: ${testUrl}`);

async function testCrawl() {
    try {
        // Launch browser with minimal config
        console.log('🚀 Launching browser...');
        const browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage'
            ]
        });
        
        console.log('✅ Browser launched');
        
        const page = await browser.newPage();
        console.log('📄 New page created');
        
        // Navigate to URL
        console.log('🔗 Navigating to URL...');
        await page.goto(testUrl, { 
            waitUntil: 'domcontentloaded', 
            timeout: 30000 
        });
        
        console.log('✅ Page loaded');
        
        // Extract content
        console.log('📝 Extracting content...');
        const content = await page.$eval('div.chapter-c', el => el.innerText);
        
        console.log(`✅ Content extracted: ${content.length} characters`);
        console.log(`Preview: ${content.substring(0, 200)}...`);
        
        // Save to file
        const outputFile = 'storage/app/temp/test_cjs_crawl.txt';
        await fs.mkdir('storage/app/temp', { recursive: true });
        await fs.writeFile(outputFile, content, 'utf-8');
        
        console.log(`✅ Content saved to: ${outputFile}`);
        
        await browser.close();
        console.log('🎉 Test completed successfully!');
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        console.error('Stack:', error.stack);
        process.exit(1);
    }
}

testCrawl();
