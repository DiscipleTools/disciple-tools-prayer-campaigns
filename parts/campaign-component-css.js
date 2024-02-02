import {css} from 'https://cdn.jsdelivr.net/gh/lit/dist@2/all/lit-all.min.js';

export const campaignStyles = css`
  .cp-wrapper {
    max-width:1000px;
    margin: 0 auto;
    padding: 2em 1em;
    border-radius: 10px;
    font-size: 1em;
    min-width: 320px;
  }

  .cp-center {
    text-align: center;
  }
  strong, b {
    font-weight: bold;
  }
  .cp-wrapper {
    font-size: 1rem;
  }
  h3 {
    font-size: 1.5rem;
  }
  h2 {
    font-size: 1.7rem;
  }
  h1, h2, h3 {
    color: black;
  }

  p {
    margin: 10px 0;
  }
  
  p, button, label {
    font-size: 1rem;
  }

  input {
    font-size: 1rem;
    line-height: 1rem;
    color: black;
    border: 1px solid var(--cp-color, 'dodgerblue');
    border-radius: 5px;
  }
  label {
    display: grid;
    font-weight: bold;
  }
  select, input[type="text"], input[type="email"], input[type="tel"], input[type="password"] {
    min-width: 250px;
    padding: 0 0.5rem;
    min-height: 40px;
    display: block;
  }


  .cp-wrapper.loading-content h2, .cp-wrapper.loading-content p {
    background-color: #ededed;
    border-radius: 100px;
    min-width: 100px;
    min-height: 20px;
    margin-bottom: 5px;
  }

  button {
    color: #fefefe;
    font-size: 1rem;
    border-radius: 5px;
    border: 1px solid transparent;
    font-weight: normal;
    padding: .85rem 1rem;
    cursor:pointer;
    background-color: var( --cp-color, dodgerblue );
    line-height: 1;
  }
  button:hover {
    background-color: transparent;
    border-color: var( --cp-color, dodgerblue );
    color: var( --cp-color, dodgerblue );
  }
  button[disabled] {
    opacity: .25;
    cursor: not-allowed;
  }
  button.danger {
    background-color: #cc4b37;
  }
  button.danger:hover {
    color: #cc4b37;
    background-color: transparent;
    border-color: #cc4b37;
  }
  button.clear-button {
    color: var( --cp-color, dodgerblue );
    background-color: transparent;
  }
  button.clear-button.danger {
    color: #cc4b37;
    background-color: transparent;
  }
  button.hollow-button {
    background-color: transparent;
    border-color: var( --cp-color, dodgerblue );
    color: var( --cp-color, dodgerblue );
  }
  button.hollow-button.danger {
    border-color: #cc4b37;
    color: #cc4b37;
  }

  a.button {
    text-decoration: none;
    color: #fefefe;
    font-size: 1rem;
    border-radius: 5px;
    border: 1px solid transparent;
    font-weight: normal;
    padding: .85rem 1rem;
    cursor:pointer;
    background-color: var( --cp-color, dodgerblue );
    line-height: 1;
  }
  a.button:hover {
    background-color: transparent;
    border-color: var( --cp-color, dodgerblue );
    color: var( --cp-color, dodgerblue );
  }

  
  .nav-buttons {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 1em;
  }
  .button-spinner {
    filter: invert(1);
  }

  label {
    font-size: 1rem;
    margin-bottom: .5rem;
  }
  .form-error {
    color: red;
  }

  .remove-prayer-time-button, .remove-daily-time-button {
    background-color: transparent;
    color: red;
    /* border: black 1px solid; */
    padding: 0 5px;
  }
  .cp-display-selected-times {
    list-style-type: none;
    padding: 0;
  }
  
  

  /**
   * Confirmation section
   */
  .success-confirmation-section {
    display: none;
    margin-top: 20px;
  }

  /**
Loader
 */

  button.loader:not(.disabled):before {
    content: '';
    position: absolute;
    right: 10px;
    top: 50%;
    margin-top: -14px;
    width: 20px;
    height: 20px;
    border: 4px solid;
    border-left-color: transparent;
    border-radius: 50%;
    filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=0);
    opacity: 0;
    -moz-transition-duration: 0.5s;
    -o-transition-duration: 0.5s;
    -webkit-transition-duration: 0.5s;
    transition-duration: 0.5s;
    -moz-transition-property: opacity;
    -o-transition-property: opacity;
    -webkit-transition-property: opacity;
    transition-property: opacity;
    -moz-animation-duration: 1s;
    -webkit-animation-duration: 1s;
    animation-duration: 1s;
    -moz-animation-iteration-count: infinite;
    -webkit-animation-iteration-count: infinite;
    animation-iteration-count: infinite;
    -moz-animation-name: rotate;
    -webkit-animation-name: rotate;
    animation-name: rotate;
    -moz-animation-timing-function: linear;
    -webkit-animation-timing-function: linear;
    animation-timing-function: linear;
    display: none;
  }

  button.loader:not(.disabled):after {
    content: '';
    height: 100%;
    width: 0;
    -moz-transition-delay: 0.5s;
    -o-transition-delay: 0.5s;
    -webkit-transition-delay: 0.5s;
    transition-delay: 0.5s;
    -moz-transition-duration: 0.75s;
    -o-transition-duration: 0.75s;
    -webkit-transition-duration: 0.75s;
    transition-duration: 0.75s;
    -moz-transition-property: width;
    -o-transition-property: width;
    -webkit-transition-property: width;
    transition-property: width;
  }

  button.loader:not(.disabled).loading {
    position: relative;
    pointer-events: none;
    cursor: not-allowed;
    padding-right: 46px;
  }

  button.loader:not(.disabled).loading:not(.expand) {
    text-align: left;
  }

  button.loader:not(.disabled).loading:before {
    -moz-transition-delay: 0.5s;
    -o-transition-delay: 0.5s;
    -webkit-transition-delay: 0.5s;
    transition-delay: 0.5s;
    -moz-transition-duration: 1s;
    -o-transition-duration: 1s;
    -webkit-transition-duration: 1s;
    transition-duration: 1s;
    filter: progid:DXImageTransform.Microsoft.Alpha(enabled=false);
    opacity: 1;
    display: block;
  }

  button.loader:not(.disabled).loading:after {
    -moz-transition-delay: 0s;
    -o-transition-delay: 0s;
    -webkit-transition-delay: 0s;
    transition-delay: 0s;
    width: 20px;
  }

  @keyframes rotate {
    0% {
      -moz-transform: rotate(0deg);
      -ms-transform: rotate(0deg);
      -webkit-transform: rotate(0deg);
      transform: rotate(0deg);
    }
    100% {
      -moz-transform: rotate(360deg);
      -ms-transform: rotate(360deg);
      -webkit-transform: rotate(360deg);
      transform: rotate(360deg);
    }
  }
  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  .dt-tag {
    display: inline-block;
    margin: 0 .5rem;
    padding: 0.5em 0.8em;
    font-size: .8rem;
    font-weight: 500;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    color: #fff;
    background-color: var(--cp-color, 'dodgerblue');
  }
  .aligned-row {
    display: flex;
    align-items: center;
  }
  
  .disabled {
    opacity: .5;
    cursor: not-allowed;
  }


`;
window.campaignStyles = campaignStyles;