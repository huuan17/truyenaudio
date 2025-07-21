#!/usr/bin/env node

const puppeteer = require('puppeteer-extra');
const StealthPlugin = require('puppeteer-extra-plugin-stealth');
const fs = require('fs').promises;
const path = require('path');

puppeteer.use(StealthPlugin());

// Nhận tham số từ command line
const [, , baseUrl, start, end, outputFolder, singleMode] = process.argv;

if (!baseUrl || !start || !end || !outputFolder) {
  console.error('❌ Thiếu tham số. Cú pháp: node crawl_original_cjs.js <baseUrl> <startChapter> <endChapter> <outputFolder> [singleMode]');
  process.exit(1);
}

const START_CHAPTER = parseInt(start);
const END_CHAPTER = parseInt(end);
const SINGLE_MODE = singleMode === '1'; // Chế độ crawl một chương duy nhất

(async () => {
  // Tạo thư mục nếu chưa tồn tại
  try {
    await fs.mkdir(outputFolder, { recursive: true });
    if (!SINGLE_MODE) {
      console.log(`✅ Thư mục lưu file: ${outputFolder}`);
    }
  } catch (e) {
    console.error('❌ Lỗi tạo thư mục:', e.message);
    return;
  }

  // Launch browser - sử dụng cấu hình từ code gốc
  const browser = await puppeteer.launch({ 
    headless: 'new', // Sử dụng headless cho production
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage'
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
      console.log(`📖 Đang duyệt: ${url}`);
    }

    try {
      await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });

      const content = await page.$eval('div.chapter-c', el => el.innerText);
      
      if (!SINGLE_MODE) {
        console.log(`✅ Lấy thành công chương ${chapter}`);
      }

      // Đường dẫn file lưu
      const filename = path.join(outputFolder, `chuong-${chapter}.txt`);
      await fs.writeFile(filename, content, 'utf-8');
      
      if (!SINGLE_MODE) {
        console.log(`💾 Đã lưu chương ${chapter} vào file ${filename}`);
      }
      
    } catch (e) {
      console.error(`❌ Lỗi chương ${chapter}:`, e.message);
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
})();
