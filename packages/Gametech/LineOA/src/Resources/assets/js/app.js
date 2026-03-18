import data from 'emoji-mart-vue-fast/data/all.json';
import 'emoji-mart-vue-fast/css/emoji-mart.css';
import { Picker, EmojiIndex } from 'emoji-mart-vue-fast';

// สร้าง index แค่ครั้งเดียว ใช้ร่วมทั้งแอป (ช่วยเรื่องความเร็ว/เมม)
const emojiIndex = new EmojiIndex(data);

// สมัครเป็น component ทั่วระบบ ใช้ชื่อ <emoji-picker> ได้เลย
Vue.component('emoji-picker', Picker);

// ถ้าอยากให้ emojiIndex ใช้ได้ใน component ต่าง ๆ ง่าย ๆ
window.__emojiIndex = emojiIndex; // วิธีบ้าน ๆ แต่ใช้ได้เลย