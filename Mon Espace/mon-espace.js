document.addEventListener('DOMContentLoaded', () => {
    const editBtn = document.getElementById('edit-btn');
    const saveBtn = document.getElementById('save-btn');
  
    const nom = document.getElementById('nom');
    const tel = document.getElementById('tel');
    const email = document.getElementById('email');
    const citation = document.getElementById('citation');
  
    let originalData = {};
  
    editBtn.addEventListener('click', () => {
      originalData.nom = nom.textContent;
      originalData.tel = tel.textContent;
      originalData.email = email.textContent;
      originalData.citation = citation.textContent;
  
      nom.innerHTML = `<input type="text" id="input-nom" value="${originalData.nom}">`;
      tel.innerHTML = `Tel: <input type="text" id="input-tel" value="${originalData.tel.replace('Tel: ', '')}">`;
      email.innerHTML = `Mail: <input type="email" id="input-email" value="${originalData.email.replace('Mail: ', '')}">`;
      citation.innerHTML = `<input type="text" id="input-citation" value="${originalData.citation}">`;
  
      editBtn.style.display = 'none';
      saveBtn.style.display = 'inline-block';
    });
  
    saveBtn.addEventListener('click', () => {
      const newNom = document.getElementById('input-nom').value;
      const newTel = document.getElementById('input-tel').value;
      const newEmail = document.getElementById('input-email').value;
      const newCitation = document.getElementById('input-citation').value;
  
      nom.textContent = newNom;
      tel.textContent = `Tel: ${newTel}`;
      email.textContent = `Mail: ${newEmail}`;
      citation.textContent = newCitation;
  
      saveBtn.style.display = 'none';
      editBtn.style.display = 'inline-block';
    });
  
    const carteTheatre = document.getElementById('carte-theatre');
    if (carteTheatre) {
      carteTheatre.style.cursor = 'pointer';
      carteTheatre.addEventListener('click', () => {
        window.location.href = '../activite/activite.html';
      });
    }
  });
  