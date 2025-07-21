const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Lấy tham số từ command line
const [, , baseUrl, startChapter, endChapter, outputFolder, singleMode] = process.argv;

if (!baseUrl || !startChapter || !endChapter || !outputFolder) {
  console.error('❌ Thiếu tham số. Sử dụng: node crawl.js <baseUrl> <startChapter> <endChapter> <outputFolder> [singleMode]');
  process.exit(1);
}

const START_CHAPTER = parseInt(startChapter);
const END_CHAPTER = parseInt(endChapter);
const SINGLE_MODE = singleMode === '1';

// Tạo thư mục đầu ra
if (!fs.existsSync(outputFolder)) {
  try {
    fs.mkdirSync(outputFolder, { recursive: true });
    console.log(`✅ Đã tạo thư mục: ${outputFolder}`);
  } catch (e) {
    console.error('❌ Lỗi tạo thư mục:', e.message);
    process.exit(1);
  }
}

async function crawlStory() {
  let browser;
  
  try {
    // Khởi động browser
    browser = await puppeteer.launch({
      headless: 'new',
      executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu'
      ]
    });

    const page = await browser.newPage();

    // Nếu ở chế độ single mode, chỉ log ra thông tin cần thiết
    if (!SINGLE_MODE) {
      console.log(`🔍 Bắt đầu crawl từ chương ${START_CHAPTER} đến chương ${END_CHAPTER}`);
    }

    for (let chapter = START_CHAPTER; chapter <= END_CHAPTER; chapter++) {
      const url = `${baseUrl}${chapter}.html`;
      
      if (!SINGLE_MODE) {
        console.log(`📖 Đang truy cập: ${url}`);
      }

      try {
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

        // Trích xuất nội dung từ div.chapter-c
        const content = await page.$eval('div.chapter-c', el => el.innerText);

        if (!content || content.trim().length === 0) {
          throw new Error('Không có nội dung');
        }

        const filename = path.join(outputFolder, `chuong-${chapter}.txt`);
        fs.writeFileSync(filename, content, 'utf-8');

        if (!SINGLE_MODE) {
          console.log(`✅ Đã lưu chương ${chapter} → ${filename}`);
        }
        
        // Delay nhỏ để tránh spam server
        await new Promise(resolve => setTimeout(resolve, 1000));
        
      } catch (e) {
        console.error(`❌ Lỗi ở chương ${chapter}:`, e.message);
        if (SINGLE_MODE) {
          await browser.close();
          process.exit(1);
        }
      }
    }

    await browser.close();
    
    if (!SINGLE_MODE) {
      console.log('🎉 Đã crawl xong tất cả chương.');
    }
    
  } catch (error) {
    console.error('❌ Lỗi crawl:', error.message);
    if (browser) {
      await browser.close();
    }
    process.exit(1);
  }
}

crawlStory();
