fetch("https://api.openweathermap.org/data/2.5/weather?lat=...&lon=...&appid=SUA_KEY")
.then(res => res.json())
.then(data => {
  const clima = data.weather[0].main;

  // enviar pro PHP (via GET ou AJAX)
});