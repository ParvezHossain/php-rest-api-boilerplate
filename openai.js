require('dotenv').config();

const axios = require('axios');
const API_KEY = process.env.OPENAI_API_KEY
const API_ENDPOINT = 'https://api.openai.com/v1/engine/davinci-codex/completions';

const { Configuration, OpenAIApi } = require("openai");

const configuration = new Configuration({
  apiKey: process.env.OPENAI_API_KEY,
});
const openai = new OpenAIApi(configuration);

async function getData(){
    const response =  await axios.get("https://api.openai.com/v1/models", {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${API_KEY}`
        }}
        );
    return response;
     
}
console.log(getData());