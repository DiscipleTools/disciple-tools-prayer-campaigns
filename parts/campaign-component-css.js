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
    background-color: var( --cp-color, 'dodgerblue' );
  }
  button:hover {
    background-color: transparent;
    border-color: var( --cp-color, 'dodgerblue' );
    color: var( --cp-color, 'dodgerblue' );
  }
  button[disabled] {
    opacity: .25;
    cursor: not-allowed;
  }
  button.clear-button {
    color: var( --cp-color, 'dodgerblue' );
    background-color: transparent;
    padding:5px;
  }
  button.danger {
    background-color: red;
  }
  button.hollow-button {
    background-color: transparent;
    border-color: var( --cp-color, 'dodgerblue' );
    color: var( --cp-color, 'dodgerblue' );
  }
  button.hollow-button.danger {
    border-color: red;
    color: red;
  }


  .cp-close-button {
    top: .5rem;
    font-size: 1em;
    line-height: 1;
    display: block;
    cursor:pointer;
    padding: 5px;
  }

  .cp-close-button img {
    filter: invert(100%);
    height: 15px;
    width: 15px;
    vertical-align: bottom;
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
 
  
 `;
window.campaignStyles = campaignStyles;