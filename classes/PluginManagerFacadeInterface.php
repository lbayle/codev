<?php
/*
The MIT License (MIT)

Copyright (c) 2014 CodevTT.org

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

/**
 * The PluginManagerFacade class is part of the CodevTT kernel (GPL v3),
 * IndicatorPlugins may be under other licenses (including non-open-source licenses). 
 * 
 * The PluginManagerFacadeInterface has an MIT license so that non-open-source plugins
 * are not under the GPL license of the PluginManagerFacade.
 * 
 * @author lbayle
 */
interface PluginManagerFacadeInterface {

   /*
    * Note: all params will not always be avalable, 
    * it is context dependent.
    */
   /*
    * TODO: will Plugins be allowed to ask for Params not defined in this list ?
    */
   const PARAM_TEAM_ID = 'teamId';
   const PARAM_SESSION_USER_ID = 'sessionUserId';
   const PARAM_START_TIMESTAMP = 'startTimestamp';
   const PARAM_END_TIMESTAMP = 'endTimestamp';
   
   public function getCodevVersion();
   public function getParam($key);
   
}
