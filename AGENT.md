# ShowForm Project Agent

## 프로젝트 개요

ShowForm은 랜딩페이지 제작기가 아니다.

ShowForm은

업종 선택
→ AI 문구 생성
→ 랜딩 자동 생성
→ 문의 수집
→ 광고 연동

을 제공하는 지역광고 자동화 SaaS 플랫폼이다.

---

## 핵심 목표

고객은 랜딩페이지를 구매하는 것이 아니다.

고객이 원하는 것은

* 문의
* 전화
* 예약
* 매출

이다.

모든 개발은 디자인보다 전환율을 우선한다.

---

## MVP 템플릿

1. 서비스 판매형
2. 병원형
3. 지역업체형

우선 이 3개만 개발한다.

---

## URL 규칙

/page/landing.php?id={id}

예)

page/landing.php?id=1

---

## 관리자 경로

/adm/landing/

---

## DB 규칙

landing_pages
landing_inquiries
landing_reviews
landing_gallery
landing_youtube
landing_notices

---

## AI 정책

모든 AI 함수는 별도 함수로 분리

generate_main_copy()
generate_faq_text()
generate_cta_text()

---

## UI 정책

모바일 우선
반응형 필수
CTA 강조
카드형 UI 사용

---

## 최종 목표

업종 입력
→ 랜딩 생성
→ 블로그 생성
→ 숏폼 생성
→ 광고문구 생성
→ 자동배포
