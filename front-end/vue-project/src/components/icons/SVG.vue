<script>
const icons = {
  // --- MENU ICONS ---
  menu: 'M4 6h16M4 12h16M4 18h16',
  dashboard:
    'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  users:
    'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  classes:
    'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  'master-data': 'M4 6h16M4 10h16M4 14h16M4 18h16',
  semesters:
    'M3 5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5z' +
    ' M8 3v4M16 3v4M3 9h18' +
    ' M15.5 15.5a2 2 0 100-4 2 2 0 000 4z' +
    ' M15.5 12v-1M15.5 18v-1M12 15.5h-1M18 15.5h-1' +
    ' M13.7 13.7l-.7-.7M17.3 17.3l-.7-.7M13.7 17.3l-.7.7M17.3 13.7l-.7.7',

  settings:
    'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
  reports:
    'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',

  // Icon Edit
  edit: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',

  // Icon Trash
  trash:
    'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',

  // Icon Chevron Left
  'chevron-left':
    'M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z',

  // Icon Chevron Right
  'chevron-right':
    'M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z',
  // --- ACTION ICONS ---
  logout:
    'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
  profile: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  camera:
    'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z',
  key: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
  'chevron-down':
    'M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z',

  // --- BUTTON ICONS ---
  upload: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
  plus: 'M12 4v16m8-8H4',
  search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',

  // --- COMPLEX ICONS ---
  eye: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`,
  },
  'eye-off': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.048 10.048 0 01-3.7 3.7m-8.2-15L19 19" />`,
  },
  spinner: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: null,
    path: `<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>`,
  },

  //khóa
  lock: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />`,
  },

  //mở khóa
  unlock: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />`,
  },
  crown: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21h14a2 2 0 002-2V7l-5 3.5L12 4 8.5 10.5 3 7v12a2 2 0 002 2z" />`,
  },
  //thêm thành viên
  'add-user': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />`,
  },
  calendar: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />`,
  },

  // Icon Hồ sơ / Thành viên thường (user-profile)
  'user-profile': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />`,
  },

  //Icon Lớp học / Giáo trình / Sách bài tập (class-book)
  'class-book': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />`,
  },

  //ĐÃ THÊM: Icon Nhóm / Tập thể (group-users)
  'group-users': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />`,
  },
  //Chat
  chat: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />`,
  },
  //Icon Công việc / Nhiệm vụ nhóm (tasks)
  tasks: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />`,
  },
  //Dấu +
  plus: 'M12 4v16m8-8H4',
  // Icon nhóm thành viên/tập thể
  'group-users': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />`,
  },
  // Icon đồng bộ/mũi tên chuyển đổi vòng tròn
  sync: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d=" M4 4v6h6 M20 20v-6h-6 M20 10a8 8 0 00-14-5l-2 2 M4 14a8 8 0 0014 5l2-2 " /> `,
  },

  // Icon chờ đợi/đồng hồ cát
  'loading-time': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`,
  },

  // Icon thành công/vòng tròn tích chọn
  success: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`,
  },

  //Icon cảnh báo nguy hiểm/chấm than tam giác
  warning: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />`,
  },
  //Clock
  clock: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" /> <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none" /> `,
  },

  play: {
    viewBox: '0 0 24 24',
    fill: 'currentColor',
    stroke: 'none',
    path: ` <path d="M8 5v14l11-7z" /> `,
  },
  check: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /> `,
  },
  // Icon Mở lại / Hoàn tác (redo) thay cho ↩
  reply: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 014 4v3m0 0l-4-4m4 4l4-4" /> `,
  },

  clipboard: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" /> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5a2 2 0 012-2h2a2 2 0 012 2v2H9V5z" /> `,
  },
  //Tài liệu
  document: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /> `,
  },
  copy: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <rect x="9" y="9" width="11" height="11" rx="2" ry="2" stroke-width="2" /> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" /> `,
  },

  'edit-pencil': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: ` <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /> `,
  },

  'back-arrow': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M15 19l-7-7 7-7"
    />
  `,
  },

  'class-book': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.5"
      d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20v-5M4 19.5v-15A2.5 2.5 0 016.5 2H20v15"
    />
  `,
  },
  //Đăng xuất
  logout: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.5"
      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
    />
  `,
  },

  upload: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M12 11v9m0-9l-3 3m3-3l3 3"
    />
  `,
  },

  'chevron-down': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M19 9l-7 7-7-7"
    />
  `,
  },

  'graduation-cap': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0119 17.764L12 21l-7-3.236a12.083 12.083 0 01.84-7.186L12 14z"
    />
  `,
  },

  'document-check': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586A1 1 0 0113.293 3.293l4.414 4.414A1 1 0 0118 8.414V19a2 2 0 01-2 2z"
    />
  `,
  },

  'check-circle': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
    />
  `,
  },

  'student-user': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M5.121 17.804A9 9 0 1118.88 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z"
    />
  `,
  },

  'chart-line': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M3 17l6-6 4 4 8-8"
    />
  `,
  },

  'chart-bar': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M7 20V10m5 10V4m5 16v-6"
    />
  `,
  },

  bolt: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M13 2L3 14h7v8l10-12h-7z"
    />
  `,
  },
  'x-circle': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M6 18L18 6M6 6l12 12"
    />
  `,
  },

  download: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M12 3v12m0 0l-4-4m4 4l4-4"
    />
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M4 17v1a2 2 0 002 2h12a2 2 0 002-2v-1"
    />
  `,
  },

  'class-book-open': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.5"
      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
    />
  `,
  },

  //verify
  'shield-check': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
    />
  `,
  },
  //quản lý lớp
  building: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.5"
      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
    />
  `,
  },

  close: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M6 18L18 6M6 6l12 12"
    />
  `,
  },

  'shield-x': {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.8"
      d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z"
    />
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.8"
      d="M9 9l6 6m0-6l-6 6"
    />
  `,
  },

  signature: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.8"
      d="M3 17s3-4 6-4 3 4 6 4 6-4 6-4M4 7h16M4 11h10"
    />
  `,
  },

  search: {
    viewBox: '0 0 24 24',
    fill: 'none',
    stroke: 'currentColor',
    path: `
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="2"
      d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"
    />
  `,
  },
}
</script>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: {
    type: String,
    required: true,
    // Validator hoạt động OK vì biến icons giờ đã nằm ở module scope
    validator: (value) => {
      return Object.keys(icons).includes(value)
    },
  },
  spin: {
    type: Boolean,
    default: false,
  },
})

// Logic xử lý computed vẫn truy cập được icons bình thường
const currentIcon = computed(() => {
  const icon = icons[props.name]

  if (!icon) return null

  if (typeof icon === 'string') {
    return {
      viewBox: '0 0 24 24',
      fill: 'none',
      stroke: 'currentColor',
      path: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${icon}" />`,
    }
  }

  return icon
})
</script>

<template>
  <svg
    v-if="currentIcon"
    xmlns="http://www.w3.org/2000/svg"
    class="h-5 w-5"
    :class="[spin ? 'animate-spin' : '']"
    :viewBox="currentIcon.viewBox"
    :fill="currentIcon.fill"
    :stroke="currentIcon.stroke"
    v-html="currentIcon.path"
  ></svg>
</template>
