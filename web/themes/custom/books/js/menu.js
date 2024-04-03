const toggleBtn = document.querySelector('#navbar-toggle');
const collapse = document.querySelector('#navbar-collapse');

toggleBtn.onclick = () => {
  collapse.classList.toggle('hidden');
  collapse.classList.toggle('flex');
};
