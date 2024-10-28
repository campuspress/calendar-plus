import styled from 'styled-components';

// @see _utils.scss in foundation-sites
const remCalc = (size, base = 16) => `${size / base}rem`;

const Backdrop = styled.div`
  position: absolute;
  top: 0;
  background: rgba(0, 0, 0, 0.7);
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 900;
  padding: 3rem;
`;

const SingleEventWrapper = styled.div`
  .single_event_inner {
    color:#333 !important;
    position: absolute !important;
    top:1em;
    z-index: 990;
    padding:2rem !important;
    background:#FFF !important;
    border:1px solid #DADADA !important;
    font-size:14px;
    left:0;
    right:0;
    .close {
      display:inline-block;
      position:absolute;
      right:${remCalc(39)};
      top:2rem;
      .dashicons {
        font-size:${remCalc(36)};
        color:#8a8a8a;
        cursor:pointer;
        &:hover {
          color:#0a0a0a;
        }
      }
    }
    > div {
      margin-bottom:.3rem;
      &.event_dates
      {
        color:#666;
        font-size:13px;
      }
    }

    h3 {
      color:#1779ba;
      font-weight:bold;
      padding-right:${remCalc(15)};
    }

    @media only screen and (min-width: 49.30769em) {
      left:3rem;
      right:3rem;
    }
  }
`;

export { Backdrop, SingleEventWrapper };
