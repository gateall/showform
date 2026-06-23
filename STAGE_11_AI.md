
# STAGE 11 - AI 문구 생성

## 목표

업종과 회사 정보를 기반으로 AI가 랜딩페이지 문구를 생성한다.

---

## AI 입력값

업종

회사명

지역명

서비스 설명

강점

---

## 생성 항목

### Hero

메인 문구

서브 문구

---

### 문제제기

problem_text

---

### 강점

strength_text

---

### FAQ

faq_text

---

### CTA

cta_text

---

## 관리자 버튼

AI 메인문구 생성

AI 강점 생성

AI FAQ 생성

AI CTA 생성

---

## 함수 구조

generate_main_copy()

generate_problem_text()

generate_strength_text()

generate_faq_text()

generate_cta_text()

---

## OpenAI 연동

환경설정

config/openai.php

---

## 프롬프트 원칙

전환율 중심

고객 혜택 중심

행동 유도 중심

---

## 예시

입력

업종

누수

지역

대구

결과

메인문구

대구 누수탐지 전문

정확한 원인 진단과 확실한 해결

CTA

지금 무료 상담받기

---

## 완료 기준

* 버튼 클릭 시 생성
* 함수 분리
* OpenAI API 교체 가능
* 프롬프트 수정 가능
