document.getElementById('amcFileInput').addEventListener('change', event => {
  const file = event.target.files[0];
  if (!file) return;
  const status = document.getElementById('amcToCifStatus');
  const output = document.getElementById('amcToCifOutput');
  const reader = new FileReader();
  reader.onload = () => {
    try {
      output.value = buildCifFromAmc(reader.result);
      status.textContent = '';
    } catch (err) {
      output.value = '';
      status.textContent = `Error: ${err.message}`;
    }
    autoSizeTextarea(output);
  };
  reader.readAsText(file);
});

document.getElementById('copyAmcToCifBtn').addEventListener('click', () => copyTextarea('amcToCifOutput'));
