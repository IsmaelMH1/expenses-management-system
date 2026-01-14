// script.js
const d = new Date();
const year = document.getElementById("year");
const todayDate = document.getElementById("todayDate");

if (year) year.textContent = d.getFullYear();
if (todayDate) todayDate.textContent = d.toISOString().slice(0, 10);
