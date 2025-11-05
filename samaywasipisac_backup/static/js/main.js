

window.addEventListener('DOMContentLoaded', () => {


  const allBtnPicker = document.querySelectorAll('.btn_pick')
  allBtnPicker?.forEach( btn => {
    btn.addEventListener('click', (e) => {
      let date = e.currentTarget.previousElementSibling
      date.showPicker()
      
      // let icon = e.currentTarget.querySelector('i');
      // icon.classList.toggle('fa-calendar')
      // icon.classList.toggle('fa-circle-xmark')
      // // console.log(icon)

      // let date = e.currentTarget.previousElementSibling
      // if (icon.classList.contains('fa-circle-xmark')) {
      //   date.showPicker()
      // } 

    })
  })




  // ************ desktop user menu button
  const userBtn = document.querySelector('#user-btn')
  userBtn?.addEventListener('click', () => {
    const userMenu = document.querySelector('#user-menu')
    const userClasses = ['flex','hidden']
    multiToggle(userMenu,userClasses)
  })

  // ************ mobile hamburguer button
  const mobileBtn = document.querySelector('#mobile-btn');
  mobileBtn?.addEventListener('click', () => {
    mobileBtn.classList.toggle('open')
    const mobileMenu = document.querySelector('#mobile-menu');
    const classes = ['flex','hidden']
    multiToggle(mobileMenu,classes)

  })

  // ************ footer acordion
  const btnAcordion = document.querySelectorAll('#btn-acordion')
  btnAcordion?.forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.nextElementSibling.classList.toggle('hidden')
      btn.lastElementChild.classList.toggle('fa-chevron-up')
      btn.lastElementChild.classList.toggle('fa-chevron-down')
    })
  })


})



const multiToggle = (el,classes) => {
  classes.map(item => el.classList.toggle(item))
}

