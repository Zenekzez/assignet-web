// Глобальна змінна, яка буде визначена в PHP файлі
// const CURRENT_USER_USERNAME_HOME_JS;

document.addEventListener('DOMContentLoaded', function () {
    const coursesGridContainer = document.getElementById('coursesGridContainer');
    const coursesPlaceholder = document.getElementById('coursesPlaceholder');

    const createCourseOption = document.getElementById('createCourseOption');
    const joinCourseOption = document.getElementById('joinCourseOption');
    const createCourseModal = document.getElementById('createCourseModal');
    const modalCloseBtnCreate = document.getElementById('modalCloseBtnCreate');
    const createCourseFormActual = document.getElementById('createCourseFormActual');

    const joinCourseModal = document.getElementById('joinCourseModal');
    const modalCloseBtnJoin = document.getElementById('modalCloseBtnJoin');
    const joinCourseFormActual = document.getElementById('joinCourseFormActual');

    const rightSidebar = document.getElementById('rightSidebar');

    function updateCoursesPlaceholderVisibility() {
        if (coursesGridContainer && coursesPlaceholder) {
            coursesPlaceholder.classList.toggle('hidden', coursesGridContainer.children.length > 0);
        }
    }

    function addCourseCardToDashboard(course) {
        if (!coursesGridContainer) return;

        const cardLink = document.createElement('a');
        cardLink.href = `course_view.php?course_id=${course.id}`;
        cardLink.classList.add('course-card');
        cardLink.setAttribute('data-course-id', course.id);

        const header = document.createElement('div');
        header.classList.add('card-header');
        if (course.color_hex) {
            header.style.backgroundColor = course.color_hex;
        } else {
            header.classList.add(course.color_class || 'course-color-default');
        }

        const title = document.createElement('h4');
        title.textContent = course.name;

        const author = document.createElement('span');
        author.classList.add('course-author');
        author.textContent = course.author_username || CURRENT_USER_USERNAME_HOME_JS; // Використовуємо глобальну змінну

        header.appendChild(title);
        header.appendChild(author);

        const body = document.createElement('div');
        body.classList.add('card-body');

        const descriptionText = document.createElement('p');
        descriptionText.classList.add('description-text');
        descriptionText.textContent = course.description || 'Немає опису.';

        body.appendChild(descriptionText);

        cardLink.appendChild(header);
        cardLink.appendChild(body);

        coursesGridContainer.appendChild(cardLink);
    }

    async function loadUserCourses() {
        try {
            const response = await fetch('../../src/get/get_user_courses.php', {
                method: 'GET',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'}
            });

            if (!response.ok) {
                console.error(`Помилка завантаження курсів: ${response.status} ${response.statusText}`);
                const errorText = await response.text(); console.error("Тіло помилки:", errorText);
                throw new Error(`Помилка завантаження курсів: ${response.statusText}`);
            }
            const data = await response.json();

            if (data.status === 'success') {
                if(coursesGridContainer) coursesGridContainer.innerHTML = '';

                if (data.teaching_courses && Array.isArray(data.teaching_courses)) {
                    data.teaching_courses.forEach(course => {
                        addCourseCardToDashboard({
                            id: course.id, name: course.name, description: course.description,
                            color_hex: course.color_hex,
                            author_username: course.author_username || CURRENT_USER_USERNAME_HOME_JS // Використовуємо глобальну змінну
                        });
                    });
                }

                if (data.enrolled_courses && Array.isArray(data.enrolled_courses)) {
                    data.enrolled_courses.forEach(course => {
                        let existingCard = coursesGridContainer.querySelector(`.course-card[data-course-id="${course.id}"]`);
                        if (!existingCard) {
                             addCourseCardToDashboard({
                                id: course.id, name: course.name, description: course.description,
                                color_hex: course.color_hex,
                                author_username: course.author_username
                            });
                        }
                    });
                }
            } else {
                console.error("Помилка отримання курсів з сервера:", data.message);
            }
        } catch (error) {
            console.error("Критична помилка при завантаженні курсів:", error);
        }
        updateCoursesPlaceholderVisibility();
    }

    if (createCourseOption && createCourseModal) {
        createCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            createCourseModal.style.display = 'flex';
            const addCourseDropdown = document.getElementById('addCourseDropdown');
            if(addCourseDropdown) addCourseDropdown.style.display = 'none';
        });
    }
    if (modalCloseBtnCreate && createCourseModal) {
        modalCloseBtnCreate.addEventListener('click', function() { createCourseModal.style.display = 'none'; });
    }
    if (createCourseModal) {
        createCourseModal.addEventListener('click', function(event) {
            if (event.target === createCourseModal) { createCourseModal.style.display = 'none';}
        });
    }
    if (joinCourseOption && joinCourseModal) {
        joinCourseOption.addEventListener('click', function(event) {
            event.preventDefault();
            joinCourseModal.style.display = 'flex';
            const addCourseDropdown = document.getElementById('addCourseDropdown');
            if(addCourseDropdown) addCourseDropdown.style.display = 'none';
        });
    }
    if (modalCloseBtnJoin && joinCourseModal) {
        modalCloseBtnJoin.addEventListener('click', function() { joinCourseModal.style.display = 'none'; });
    }
    if (joinCourseModal) {
        joinCourseModal.addEventListener('click', function(event) {
            if (event.target === joinCourseModal) { joinCourseModal.style.display = 'none'; }
        });
    }

    if (createCourseFormActual && createCourseModal) {
        createCourseFormActual.addEventListener('submit', async function(event) {
            event.preventDefault();
            const courseNameInput = document.getElementById('modalCourseName');
            const courseDescriptionInput = document.getElementById('modalCourseDescription');
            const courseName = courseNameInput.value.trim();
            const courseDescription = courseDescriptionInput.value.trim();

            if (!courseName) { alert('Назва курсу не може бути порожньою.'); courseNameInput.focus(); return; }

            const formData = new FormData();
            formData.append('course_name', courseName);
            formData.append('description', courseDescription);

            try {
                const response = await fetch('../../src/actions/create_course_process.php', { method: 'POST', body: formData });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `Помилка сервера: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.status === 'success' && data.course) {
                    addCourseCardToDashboard({
                        id: data.course.id, name: data.course.name, description: data.course.description,
                        color_hex: data.course.color_hex,
                        author_username: data.course.author_username || CURRENT_USER_USERNAME_HOME_JS // Використовуємо глобальну змінну
                    });
                    updateCoursesPlaceholderVisibility();
                    createCourseModal.style.display = 'none';
                    this.reset();
                } else {
                    alert(data.message || 'Не вдалося створити курс.');
                }
            } catch (error) {
                console.error('Помилка при створенні курсу:', error); alert(`Сталася помилка: ${error.message}`);
            }
        });
    }

    if (joinCourseFormActual && joinCourseModal) {
        joinCourseFormActual.addEventListener('submit', async function(event) {
            event.preventDefault();
            const courseCodeInput = document.getElementById('modalCourseCode');
            const courseCode = courseCodeInput.value.trim();
            if (!courseCode) { alert('Будь ласка, введіть код курсу.'); courseCodeInput.focus(); return; }

            const formData = new FormData();
            formData.append('course_code', courseCode);

            try {
                const response = await fetch('../../src/actions/join_course_process.php', { method: 'POST', body: formData });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => null);
                    throw new Error(errorData?.message || `Помилка сервера: ${response.statusText}`);
                }
                const data = await response.json();
                if (data.status === 'success' && data.course) {
                    let existingCard = coursesGridContainer.querySelector(`.course-card[data-course-id="${data.course.id}"]`);
                    if (!existingCard) {
                         addCourseCardToDashboard({
                            id: data.course.id,
                            name: data.course.name,
                            description: data.course.description,
                            color_hex: data.course.color_hex,
                            author_username: data.course.author_username
                         });
                    }
                    updateCoursesPlaceholderVisibility();
                    joinCourseModal.style.display = 'none';
                    this.reset();
                    alert(data.message);
                } else {
                    alert(data.message || 'Не вдалося приєднатися до курсу.');
                }
            } catch (error) {
                console.error('Помилка при приєднанні до курсу:', error); alert(`Сталася помилка: ${error.message}`);
            }
        });
    }

    const screenBreakpointRightSidebar = 1199.98;
    function applyRightSidebarResponsive() {
        if (!rightSidebar) return;
        const screenWidth = window.innerWidth;
        if (screenWidth <= screenBreakpointRightSidebar) {
            rightSidebar.classList.add('hidden');
        } else {
            // Код для показу сайдбару на десктопі, якщо потрібно
            // rightSidebar.classList.remove('hidden');
        }
    }
    if (rightSidebar) {
        window.addEventListener('resize', applyRightSidebarResponsive);
        applyRightSidebarResponsive();
    }

    loadUserCourses();
});