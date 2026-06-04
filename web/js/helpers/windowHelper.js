/**
 * Открывает новое окно браузера и даёт ему фокус
 * @param url
 * @param width
 * @param height
 * @param resizable
 */
function openSmallWindow(url, width = 400, height = 300, resizable = 'yes') {
    const winOptions = 'width='+width+',height='+height+',resizable='+resizable+',status=no,location=no';
    const newWindow = window.open(url, '', winOptions);

    if (newWindow) {
        newWindow.focus(); // Фокус на вновь открытое окно
    } else {
        alert('Блокировщик всплывающих окон помешал открытию окна с паролем.');
    }
}